let lastMessageId = 0;
let pollInterval = null;

async function fetchPoll() {
    try {
        const response = await fetch(`${BASE_URL}api/poll.php?last_msg=${lastMessageId}&_t=${Date.now()}`, { cache: 'no-store' });
        if (!response.ok) return;
        
        const data = await response.json();
        
        if (data.error === 'auth_error') {
            window.location.reload();
            return;
        }

        lastMessageId = data.last_msg;

        data.events.forEach(event => {
            if (event.type === 'new_message') {
                handleNewMessage(event.data);
            } else if (event.type === 'read_receipts') {
                handleReadReceipts(event.data);
            } else if (event.type === 'online_status') {
                updateOnlineStatus(event.data);
            }
        });
    } catch (e) {
        // Silently fail and retry next interval
    }
}

function initSSE() {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
    // Fetch immediately
    fetchPoll();
    // Then poll every 3 seconds
    pollInterval = setInterval(fetchPoll, 3000);
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
        if (typeof activeConversationId !== 'undefined' && activeConversationId) {
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

    if (changed && typeof activeConversationId !== 'undefined' && activeConversationId) {
        // Just reload messages if a read receipt arrived for the active chat
        // To be simpler than searching DOM elements
        if (typeof loadMessages === 'function') {
            loadMessages(activeConversationId);
        }
    }
}

function handleNewMessage(msg) {
    // Play sound ALWAYS
    playNotificationSound();
    
    // Show Push/Toast
    showPushNotification(msg);

    if (typeof activeConversationId !== 'undefined' && activeConversationId == msg.conversation_id) {
        if (typeof renderMessage === 'function') {
            renderMessage(msg);
            scrollToBottom();
            // Mark as read immediately since we are in the chat
            fetchAPI('api/mark_read.php', {
                method: 'POST',
                body: JSON.stringify({ conversation_id: msg.conversation_id }),
                headers: { 'Content-Type': 'application/json' }
            });
        }
    }
    
    // Always refresh chat list to update unread badge / last message
    if (typeof loadChats === 'function') {
        loadChats();
    }
}

function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(e => console.log('Audio autoplay prevented'));
    }
}

function showPushNotification(msg) {
    const title = msg.group_name ? `Nuevo mensaje en ${msg.group_name}` : `Mensaje de ${msg.sender_name || 'Alguien'}`;
    const body = msg.message || 'Archivo adjunto recibido';
    
    // In-app Toast using SweetAlert2
    if (typeof Swal !== 'undefined') {
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
    }

    // OS Push Notification
    if ("Notification" in window && Notification.permission === 'granted') {
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
    
    initSSE();
});


