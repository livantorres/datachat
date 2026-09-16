let lastMessageId = 0;
let eventSource = null;

function initSSE() {
    if (eventSource) {
        eventSource.close();
    }

    eventSource = new EventSource(`api/stream.php?last_msg=${lastMessageId}`);

    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        if (data.type === 'new_message') {
            handleNewMessage(data.data);
        } else if (data.type === 'read_receipts') {
            handleReadReceipts(data.data);
        } else if (data.type === 'online_status') {
            updateOnlineStatus(data.data);
        } else if (data.type === 'auth_error') {
            window.location.reload();
        }
    };

    eventSource.onerror = function(err) {
        // SSE auto-reconnects, no need to clutter console
    };
}

let knownReadMax = {};

function updateOnlineStatus(onlineUsers) {
    // Modify chatList in memory
    let changed = false;
    if (typeof chatList !== 'undefined') {
        chatList.forEach(chat => {
            if (chat.type === 'private' && chat.contact_id) {
                const isNowOnline = onlineUsers.includes(parseInt(chat.contact_id)) || onlineUsers.includes(String(chat.contact_id));
                if (chat.is_online !== isNowOnline) {
                    chat.is_online = isNowOnline;
                    changed = true;
                }
            }
        });
        if (changed) renderChatList();
        
        // Update active chat header if open
        if (activeConversationId) {
            const activeChat = chatList.find(c => c.id == activeConversationId);
            if (activeChat && activeChat.type === 'private') {
                document.getElementById('activeChatStatus').textContent = activeChat.is_online ? 'Online' : 'Offline';
            }
        }
    }
}

function handleReadReceipts(receipts) {
    let changed = false;
    receipts.forEach(r => {
        if (!knownReadMax[r.conversation_id] || knownReadMax[r.conversation_id] < r.max_read_id) {
            knownReadMax[r.conversation_id] = r.max_read_id;
            changed = true;
        }
    });

    if (changed && activeConversationId) {
        // Just reload messages if a read receipt arrived for the active chat
        // To be simpler than searching DOM elements
        loadMessages(activeConversationId);
    }
}

function handleNewMessage(msg) {
    lastMessageId = Math.max(lastMessageId, msg.id);

    // Play sound ALWAYS
    playNotificationSound();
    
    // Show Push/Toast
    showPushNotification(msg);

    if (activeConversationId == msg.conversation_id) {
        renderMessage(msg);
        scrollToBottom();
        // Mark as read immediately since we are in the chat
        fetchAPI('api/mark_read.php', {
            method: 'POST',
            body: JSON.stringify({ conversation_id: msg.conversation_id }),
            headers: { 'Content-Type': 'application/json' }
        });
    }
    
    // Always refresh chat list to update unread badge / last message
    loadChats();
}

function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    audio.currentTime = 0;
    audio.play().catch(e => console.log('Audio autoplay prevented'));
}

function showPushNotification(msg) {
    const title = msg.group_name ? `Nuevo mensaje en ${msg.group_name}` : `Mensaje de ${msg.sender_name || 'Alguien'}`;
    const body = msg.message || 'Archivo adjunto recibido';
    
    // In-app Toast using SweetAlert2
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        title: title,
        text: body,
        icon: 'info'
    });

    // OS Push Notification
    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: 'uploads/logos/default.png'
        });
    }
}

// Request permission on load
document.addEventListener('DOMContentLoaded', () => {
    if ("Notification" in window) {
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    }
    
    // First we get max id via API if needed, but the PHP stream handles last_msg=0 to just set max id.
    initSSE();
});
