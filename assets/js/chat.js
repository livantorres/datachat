let activeConversationId = null;
let chatList = [];

document.addEventListener('DOMContentLoaded', () => {
    loadChats().then(() => {
        if (typeof OPEN_CHAT_USER !== 'undefined' && OPEN_CHAT_USER !== null) {
            startPrivateChat(OPEN_CHAT_USER);
        }
    });

    // Responsive back button (or ESC equivalent)
    const closeChat = () => {
        activeConversationId = null;
        document.getElementById('sidebar').classList.remove('d-none', 'hide-mobile');
        document.getElementById('chatArea').classList.add('d-none');
        document.getElementById('chatArea').classList.remove('d-flex');
        document.getElementById('chatBlankState').classList.remove('d-none');
        document.getElementById('chatHeader').classList.add('d-none');
        document.getElementById('chatHeader').classList.remove('d-flex');
        document.getElementById('messagesBox').classList.add('d-none');
        document.getElementById('messagesBox').classList.remove('d-flex');
        document.getElementById('inputArea').classList.add('d-none');
        document.getElementById('inputArea').classList.remove('d-flex');
        renderChatList();
    };

    document.getElementById('btnBack').addEventListener('click', closeChat);

    // ESC to close chat
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && activeConversationId) {
            closeChat();
        }
    });

    // New Chat Modal - Load contacts when opened
    document.getElementById('modalNewChat').addEventListener('show.bs.modal', () => {
        loadContacts();
    });

    // Send Message
    document.getElementById('formSendMessage').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const hasFiles = document.getElementById('fileInput').files.length > 0;
        if (!formData.get('message').trim() && !hasFiles) return;

        // Optimistic UI for text
        const textMsg = formData.get('message');
        if(textMsg && !hasFiles) {
             renderMessage({
                sender_id: CURRENT_USER_ID,
                message: textMsg,
                created_at: new Date().toISOString(),
                attachment: null,
                is_read: 0
            });
            document.getElementById('messageInput').value = '';
            scrollToBottom();
        }

        const res = await fetch('api/send_message.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(!data.success) {
            showToast(data.message, 'error');
        } else {
            document.getElementById('fileInput').value = '';
            document.getElementById('messageInput').value = '';
            loadMessages(activeConversationId);
        }
        
    });

    // Attachment clicks
    document.getElementById('attachImage').addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('fileInput').setAttribute('accept', 'image/*');
        document.getElementById('fileInput').click();
    });
    document.getElementById('attachDocument').addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('fileInput').setAttribute('accept', '.pdf,.doc,.docx,.xls,.xlsx,.txt');
        document.getElementById('fileInput').click();
    });

    // Previsualizar al seleccionar archivo
    document.getElementById('fileInput').addEventListener('change', (e) => {
        const files = e.target.files;
        if (files.length === 0) return;

        let previewHtml = '<div class="d-flex flex-wrap justify-content-center gap-2">';
        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            if (file.type.startsWith('image/')) {
                const url = URL.createObjectURL(file);
                previewHtml += '<img src="' + url + '" class="img-thumbnail" style="max-height: 100px; max-width: 100px; object-fit: cover;">';
            } else {
                previewHtml += '<div class="p-2 bg-light rounded text-center border" style="width: 100px; height: 100px; overflow: hidden;"><i class="bi bi-file-earmark-check fs-3 text-primary"></i><br><small style="font-size:0.7rem;">' + file.name + '</small></div>';
            }
        }
        previewHtml += '</div>';

        Swal.fire({
            title: 'Enviar adjunto',
            html: previewHtml,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-send"></i> Enviar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formSendMessage').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            } else {
                document.getElementById('fileInput').value = '';
            }
        });
    });
});

async function loadChats() {
    const data = await fetchAPI('api/get_chats.php');
    if (data.success) {
        chatList = data.data;
        renderChatList();
    }
}

function renderChatList() {
    const container = document.getElementById('chatList');
    container.innerHTML = '';
    
    if (chatList.length === 0) {
        container.innerHTML = '<div class="text-center text-muted mt-4">No tienes conversaciones. Inicia una nueva.</div>';
        return;
    }

    chatList.forEach(chat => {
        const div = document.createElement('div');
        div.className = `p-3 border-bottom chat-item d-flex align-items-center ${activeConversationId === chat.id ? 'active' : ''}`;
        div.onclick = () => openChat(chat);
        
        let statusHtml = '';
        if (chat.type === 'private') {
            statusHtml = `<span class="status-indicator ${chat.is_online ? 'status-online' : 'status-offline'} position-absolute" style="bottom: 0; right: 0; border: 2px solid white;"></span>`;
        }

        let unreadBadge = '';
        if (chat.unread_count > 0 && chat.id != activeConversationId) {
            unreadBadge = `<span class="badge bg-success rounded-pill ms-2">${chat.unread_count}</span>`;
        }

        let prefix = '';
        if (chat.is_mine && chat.last_message) prefix = 'T√∫: ';

        div.innerHTML = `
            <div class="position-relative me-3">
                <img src="${chat.avatar}" alt="" class="rounded-circle avatar-md" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(chat.name)}&background=random'">
                ${statusHtml}
            </div>
            <div class="flex-grow-1 min-vw-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-truncate">${chat.name}</h6>
                    <small class="text-muted text-nowrap">${chat.time}</small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted text-truncate" style="font-size: 0.85rem;">
                        ${prefix}${chat.last_message ? chat.last_message : (chat.last_message === null ? 'Adjunto' : 'Sin mensajes')}
                    </div>
                    ${unreadBadge}
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

async function openChat(chat) {
    activeConversationId = chat.id;
    document.getElementById('activeConversationId').value = chat.id;
    
    // UI Updates
    document.getElementById('chatBlankState').classList.add('d-none');
    document.getElementById('chatHeader').classList.remove('d-none');
    document.getElementById('chatHeader').classList.add('d-flex');
    document.getElementById('messagesBox').classList.add('d-none');
    document.getElementById('chatLoading').classList.remove('d-none');
    document.getElementById('chatLoading').classList.add('d-flex');
    
    document.getElementById('inputArea').classList.remove('d-none');
    document.getElementById('inputArea').classList.add('d-flex');
    
    document.getElementById('activeChatName').textContent = chat.name;
    const avatarImg = document.getElementById('activeChatAvatar');
    avatarImg.src = chat.avatar;
    avatarImg.onerror = () => { avatarImg.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(chat.name)}&background=random`; };
    document.getElementById('activeChatStatus').textContent = chat.type === 'private' ? (chat.is_online ? 'Online' : 'Offline') : 'Grupo';

    // Mobile view switch
    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.add('hide-mobile');
        document.getElementById('chatArea').classList.remove('d-none');
        document.getElementById('chatArea').classList.add('d-flex');
    }

    // Mark as read
    fetchAPI('api/mark_read.php', {
        method: 'POST',
        body: JSON.stringify({ conversation_id: chat.id }),
        headers: { 'Content-Type': 'application/json' }
    });

    renderChatList(); // Update active class
    // Cargar mensajes
    await loadMessages(chat.id);
    
    // Enfocar autom√°ticamente el input del chat
    const msgInput = document.getElementById('messageInput');
    if (msgInput) {
        msgInput.focus();
    }
}

async function loadMessages(chatId) {
    const data = await fetchAPI(`api/get_messages.php?conversation_id=${chatId}`);
    if (data.success) {
        const box = document.getElementById('messagesBox');
        box.innerHTML = '';
        document.getElementById('chatLoading')?.classList.remove('d-flex');
        document.getElementById('chatLoading')?.classList.add('d-none');
        box.classList.remove('d-none');
        box.classList.add('d-flex');
        data.data.forEach(msg => renderMessage(msg, box));
        scrollToBottom();
    }
}

function renderMessage(msg, container = document.getElementById('messagesBox')) {
    const isMe = msg.sender_id == CURRENT_USER_ID;
    const div = document.createElement('div');
    div.id = msg.id ? `msg-${msg.id}` : `msg-tmp-${Date.now()}`;
    div.className = `message-bubble ${isMe ? 'message-out' : 'message-in'} position-relative`;
    
    let content = '';
    
    if (isMe && msg.id) {
        content += `<div class="dropdown d-inline-block position-absolute" style="top: 2px; right: 5px; z-index: 5;">
            <i class="bi bi-chevron-down text-muted opacity-50" data-bs-toggle="dropdown" style="cursor:pointer; font-size: 0.75rem;"></i>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 120px;">
                <li><a class="dropdown-item text-danger py-1" href="#" onclick="deleteMessage(${msg.id}); return false;" style="font-size:0.85rem;"><i class="bi bi-trash"></i> Eliminar</a></li>
            </ul>
        </div>`;
    }

    // Show sender name in groups if not me
    if (!isMe && msg.sender_name) {
        content += `<div class="fw-bold text-primary" style="font-size: 0.8rem;">${msg.sender_name}</div>`;
    }

    if (msg.message) {
        content += `<div>${msg.message}</div>`;
    }

    if (msg.attachment) {
        content += renderAttachment(msg.attachment, msg.attachment_type, msg.id, isMe);
    }

    let tickHtml = '';
    if (isMe) {
        const isRead = msg.is_read == 1;
        tickHtml = `<span class="msg-tick ${isRead ? 'read' : 'sent'}"><i class="bi bi-check-all"></i></span>`;
    }

    content += `<span class="message-time">${formatTime(msg.created_at)}${tickHtml}</span>`;
    div.innerHTML = content;
    container.appendChild(div);
}

function renderAttachment(filename, type, msgId, isMe) {
    const url = `uploads/attachments/${filename}`;
    if (type === 'image') {
        return `<img src="${url}" class="attachment-preview img-fluid" alt="Adjunto" style="cursor:pointer;" onclick="openImageModal('${url}', ${msgId}, ${isMe})">`;
    } else if (type === 'pdf') {
        return `<a href="${url}" target="_blank" class="attachment-file"><i class="bi bi-file-earmark-pdf text-danger"></i> <div>PDF<br><small>Clic para ver/descargar</small></div></a>`;
    } else if (type === 'word') {
        const wordUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(window.location.origin + '/' + url)}`;
        return `<a href="${wordUrl}" target="_blank" class="attachment-file"><i class="bi bi-file-earmark-word text-primary"></i> <div>Word<br><small>Ver en l√≠nea</small></div></a>`;
    } else if (type === 'excel') {
        const excelUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(window.location.origin + '/' + url)}`;
        return `<a href="${excelUrl}" target="_blank" class="attachment-file"><i class="bi bi-file-earmark-excel text-success"></i> <div>Excel<br><small>Ver en l√≠nea</small></div></a>`;
    } else {
        return `<a href="${url}" target="_blank" class="attachment-file"><i class="bi bi-file-earmark-text text-secondary"></i> <div>Archivo<br><small>Descargar</small></div></a>`;
    }
}

function scrollToBottom() {
    const box = document.getElementById('messagesBox');
    box.scrollTop = box.scrollHeight;
}

// Contacts Loading
async function loadContacts() {
    const data = await fetchAPI('api/get_contacts.php');
    if (data.success) {
        const list = document.getElementById('contactsList');
        const gList = document.getElementById('groupMembersList');
        list.innerHTML = '';
        gList.innerHTML = '';

        data.data.forEach(user => {
            // Contacts tab
            const btn = document.createElement('button');
            btn.className = 'list-group-item list-group-item-action d-flex align-items-center';
            btn.innerHTML = `<img src="uploads/avatars/${user.avatar}" class="rounded-circle avatar-sm me-3" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}'"> <span>${user.name}</span>`;
            btn.onclick = () => startPrivateChat(user.id);
            list.appendChild(btn);

            // Group tab checkboxes
            const cbDiv = document.createElement('div');
            cbDiv.className = 'form-check mb-2';
            cbDiv.innerHTML = `
                <input class="form-check-input" type="checkbox" name="members[]" value="${user.id}" id="user_${user.id}">
                <label class="form-check-label d-flex align-items-center" for="user_${user.id}">
                    <img src="uploads/avatars/${user.avatar}" class="rounded-circle me-2" style="width:24px;height:24px;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}'">
                    ${user.name}
                </label>
            `;
            gList.appendChild(cbDiv);
        });
    }
}

async function startPrivateChat(contactId) {
    const data = await fetchAPI('api/create_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'private', contact_id: contactId })
    });
    
    if (data.success) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNewChat'));
        if (modal) modal.hide();
        await loadChats();
        const chat = chatList.find(c => c.id == data.conversation_id);
        if (chat) openChat(chat);
    }
}

document.getElementById('formCreateGroup').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = e.target.group_name.value;
    const members = Array.from(e.target.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
    
    if (members.length === 0) {
        showToast('Selecciona al menos un miembro', 'warning');
        return;
    }

    const data = await fetchAPI('api/create_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'group', name: name, members: members })
    });

    if (data.success) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNewChat'));
        if (modal) modal.hide();
        await loadChats();
        const chat = chatList.find(c => c.id == data.conversation_id);
        if (chat) openChat(chat);
    }
});




async function deleteMessage(id) {
    Swal.fire({
        title: "øEliminar mensaje?",
        text: "øSeguro que deseas eliminar este mensaje?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "SÌ, eliminar",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    }).then(async (result) => {
        if (result.isConfirmed) {
            const data = await fetchAPI("api/delete_message.php", { method: "POST", body: JSON.stringify({message_id: id}), headers: {"Content-Type": "application/json"} });
            if(data.success) {
                document.getElementById("msg-"+id)?.remove();
                showToast("Mensaje eliminado");
            } else {
                showToast(data.message, "error");
            }
        }
    });
}

function openImageModal(url, msgId, isMe) {
    let html = `<img src="${url}" class="img-fluid w-100 rounded">`;
    html += `<div class="mt-3 d-flex justify-content-center gap-2">`;
    html += `<a href="${url}" download target="_blank" class="btn btn-primary"><i class="bi bi-download"></i> Descargar</a>`;
    if (isMe && msgId) {
        html += `<button class="btn btn-danger" onclick="Swal.close(); deleteMessage(${msgId})"><i class="bi bi-trash"></i> Eliminar</button>`;
    }
    html += `</div>`;
    
    Swal.fire({
        html: html,
        showConfirmButton: false,
        showCloseButton: true,
        width: "auto",
        customClass: {
            popup: "p-3"
        }
    });
}



