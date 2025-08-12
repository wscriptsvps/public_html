document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chat-container');
    const messagesBox = document.getElementById('messages-box');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const onlineUsersList = document.getElementById('online-users-list');
    const onlineUserCountSpan = document.getElementById('online-user-count');
    const privateIndicator = document.getElementById('private-chat-indicator');
    const publicReplyIndicator = document.getElementById('public-reply-indicator');
    const leaveRoomBtn = document.getElementById('leave-room-btn');
    const adblockWarning = document.getElementById('adblock-warning');
    const clearRoomBtn = document.getElementById('clear-room-btn');
    
    const currentUserId = parseInt(chatContainer.dataset.currentUserId, 10);
    const accountType = chatContainer.dataset.accountType;
    const charLimit = parseInt(chatContainer.dataset.charLimit, 10) || 1024;
    
    messageInput.maxLength = charLimit;
    
    // Elementos do Modal de Interação
    const userInteractionModal = document.getElementById('user-interaction-modal');
    const interactionUsername = document.getElementById('interaction-username');
    const interactionPublicBtn = document.getElementById('interaction-public-btn');
    const interactionPrivateBtn = document.getElementById('interaction-private-btn');
    const interactionReportBtn = document.getElementById('interaction-report-btn');
    const interactionVotekickBtn = document.getElementById('interaction-votekick-btn');
    const interactionCancelBtn = document.getElementById('interaction-cancel-btn');

    // Elementos do Modal de Denúncia
    const reportModal = document.getElementById('report-modal');
    const reportForm = document.getElementById('report-form');
    const reportCancelBtn = document.getElementById('report-cancel-btn');
    const reportUsernameSpan = document.getElementById('report-username');
    const reportUserIdInput = document.getElementById('report-user-id');
    const reportFeedback = document.getElementById('report-feedback');

    // Elementos da Barra de Votação
    const votekickBanner = document.getElementById('votekick-banner');
    const votekickTargetName = document.getElementById('votekick-target-name');
    const votekickCurrentVotes = document.getElementById('votekick-current-votes');
    const votekickCastVoteBtn = document.getElementById('votekick-cast-vote-btn');
    const votekickProgressBar = document.getElementById('votekick-progress-bar');

    let lastMessageId = 0;
    let replyInfo = null;
    let messageHistory = [];
    let blockedUserIds = new Set();
    let currentVoteInfo = null;

    const setReplyState = (info) => {
        replyInfo = info;
        privateIndicator.classList.add('hidden');
        publicReplyIndicator.classList.add('hidden');
        if (info) {
            if (info.type === 'private') {
                privateIndicator.querySelector('span').textContent = `Conversa reservada com ${escapeHTML(info.name)}.`;
                privateIndicator.classList.remove('hidden');
                privateIndicator.classList.add('flex');
            } else {
                publicReplyIndicator.querySelector('span').textContent = `Falando abertamente para ${escapeHTML(info.name)}.`;
                publicReplyIndicator.classList.remove('hidden');
                publicReplyIndicator.classList.add('flex');
            }
        }
        messageInput.focus();
    };

    const cancelReply = () => setReplyState(null);

    const detectAdBlocker = () => {
        if (accountType !== 'common') return;
        const adBait = document.createElement('div');
        adBait.innerHTML = '&nbsp;';
        adBait.className = 'adsbox ad-unit textads banner-ads ad-placement';
        adBait.style.position = 'absolute';
        adBait.style.left = '-9999px';
        document.body.appendChild(adBait);
        setTimeout(() => {
            if (adBait.offsetHeight === 0) {
                messageInput.disabled = true;
                messageForm.querySelector('button').disabled = true;
                messageInput.placeholder = 'Desative o bloqueador de anúncios para conversar.';
                adblockWarning.classList.remove('hidden');
            }
            document.body.removeChild(adBait);
        }, 200);
    };

    const fetchRoomState = async () => {
        try {
            const response = await fetch(`/chat/ajax/get_room_state.php?last_id=${lastMessageId}`);
            const data = await response.json();
            if (data.status === 'success') {
                const shouldScroll = (messagesBox.scrollTop + messagesBox.clientHeight) >= messagesBox.scrollHeight - 10;
                if (data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        messageHistory.push(msg);
                        appendMessage(msg, false);
                        lastMessageId = msg.id;
                    });
                    if (shouldScroll) messagesBox.scrollTop = messagesBox.scrollHeight;
                }
                updateOnlineUsersList(data.onlineUsers);
                updateVotekickUI(data.activeVote);
            }
        } catch (error) {
            console.error('Erro ao buscar estado da sala:', error);
        }
    };

    const appendSystemMessage = (text, type = 'info') => {
        const msgElement = document.createElement('div');
        msgElement.classList.add('message-item', 'mb-1', 'text-sm');
        const colorClass = type === 'error' ? 'text-red-500' : 'text-gray-500';
        const time = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        msgElement.innerHTML = `<span class="text-gray-500">${time}</span> - <span class="${colorClass} italic font-semibold">${escapeHTML(text)}</span>`;
        messagesBox.appendChild(msgElement);
        messagesBox.scrollTop = messagesBox.scrollHeight;
    };

    const originalAppendMessage = (msg, shouldScroll = true) => {
        const msgElement = document.createElement('div');
        const time = new Date(msg.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        
        msgElement.classList.add('message-item', 'mb-1', 'text-sm', 'break-words');
        let fullMessage = '';

        if (msg.type === 'system') {
            fullMessage = `
                <span class="text-gray-500 italic">
                    ${time} - <img src="/uploads/avatars/${msg.user_avatar}" class="w-5 h-5 rounded-full inline-block mx-1 align-middle"> <strong>${escapeHTML(msg.display_name)}</strong> ${escapeHTML(msg.content)}
                </span>
            `;
        } else {
            let header = '';
            if (msg.type === 'private') {
                header = `<strong style="color: ${msg.user_color};">${escapeHTML(msg.display_name)}</strong> <span class="text-gray-600">(reservadamente para ${escapeHTML(msg.recipient_display_name)}):</span>`;
            } else if (msg.recipient_display_name) {
                header = `<strong style="color: ${msg.user_color};">${escapeHTML(msg.display_name)}</strong> <span class="text-gray-600">fala para</span> <strong style="color: ${msg.user_color};">${escapeHTML(msg.recipient_display_name)}:</strong>`;
            } else {
                header = `<strong style="color: ${msg.user_color};">${escapeHTML(msg.display_name)}:</strong>`;
            }

            const formattedContent = formatMessageContent(msg.content);
            fullMessage = `
                <span class="text-gray-500">${time}</span> - 
                <img src="/uploads/avatars/${msg.user_avatar}" class="w-5 h-5 rounded-full inline-block mx-1 align-middle"> 
                ${header} 
                <span class="text-gray-800">${formattedContent}</span>
            `;
        }
        
        msgElement.innerHTML = fullMessage;
        messagesBox.appendChild(msgElement);
        if (shouldScroll) messagesBox.scrollTop = messagesBox.scrollHeight;
    };
    
    const appendMessage = (msg, shouldScroll = true) => {
        if (blockedUserIds.has(msg.user_id)) return;
        originalAppendMessage(msg, shouldScroll);
    };

    const escapeHTML = (str) => {
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str || ''));
        return p.innerHTML;
    };

    const formatMessageContent = (content) => {
        const words = content.split(/(\s+)/);
        return words.map(word => {
            const urlRegex = /^(https?:\/\/[^\s]+)/;
            if (urlRegex.test(word)) {
                const url = word;
                if (/\.(jpeg|jpg|gif|png|webp)$/i.test(url)) return `<br><a href="${url}" target="_blank" rel="noopener noreferrer"><img src="${url}" class="max-w-xs max-h-64 rounded-lg mt-2 border"></a>`;
                const youtubeMatch = url.match(/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                if (youtubeMatch) return `<br><iframe class="mt-2 rounded-lg" style="max-width: 100%;" width="480" height="270" src="https://www.youtube.com/embed/${youtubeMatch[1]}" frameborder="0" allowfullscreen></iframe>`;
                if (/\.mp3$/i.test(url)) return `<br><audio controls class="mt-2 w-full max-w-xs" src="${url}"></audio>`;
                return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${escapeHTML(url)}</a>`;
            } else {
                return escapeHTML(word);
            }
        }).join('');
    };
    
    const updateOnlineUsersList = (users) => {
        onlineUserCountSpan.textContent = `(${users.length})`;
        onlineUsersList.innerHTML = '';
        let currentUser = null;
        let otherUsers = [];
        users.forEach(user => {
            if (user.id === currentUserId) currentUser = user;
            else otherUsers.push(user);
        });
        otherUsers.sort((a, b) => a.display_name.localeCompare(b.display_name));
        const sortedUsers = currentUser ? [currentUser, ...otherUsers] : otherUsers;
        if (sortedUsers.length === 0) {
            onlineUsersList.innerHTML = '<li class="text-gray-500">Ninguém online.</li>';
            return;
        }
        sortedUsers.forEach(user => {
            const userElement = document.createElement('li');
            const isCurrentUser = (user.id === currentUserId);
            userElement.classList.add('flex', 'items-center', 'gap-2', 'p-2', 'rounded-md');
            if (!isCurrentUser) userElement.classList.add('hover:bg-gray-200');
            else userElement.classList.add('bg-purple-100');
            userElement.dataset.userId = user.id;
            userElement.dataset.userName = user.display_name;
            const youText = isCurrentUser ? ' (Você)' : '';
            
            let genderIcon = '';
            if (user.gender === 'male') {
                genderIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" /></svg>';
            } else if (user.gender === 'female') {
                genderIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-pink-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>';
            }
            
            let adminIcons = '';
            if (accountType === 'admin' && !isCurrentUser) {
                adminIcons = `
                    <button title="Banir Utilizador" class="text-gray-400 hover:text-red-600 ban-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                    </button>
                `;
            }
            const isBlocked = blockedUserIds.has(user.id);
            userElement.innerHTML = `
                <img src="/uploads/avatars/${user.avatar}" class="w-6 h-6 rounded-full">
                ${genderIcon}
                <span class="font-medium text-sm text-gray-800 flex-grow cursor-pointer">${escapeHTML(user.display_name)}${youText}</span>
                ${user.account_type === 'vip' ? '<span title="VIP" class="text-yellow-500">★</span>' : ''}
                <div class="flex items-center gap-2 action-icons">
                    ${adminIcons}
                    ${!isCurrentUser ? `
                    <button title="${isBlocked ? 'Desbloquear' : 'Bloquear'}" class="text-gray-400 hover:text-gray-600 block-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.33 4.33a6 6 0 018.486 8.486L4.33 4.33z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button title="Reportar" class="text-gray-400 hover:text-red-600 report-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd" /></svg>
                    </button>
                    ` : ''}
                </div>
            `;
            const nameSpan = userElement.querySelector('span.flex-grow');
            if (nameSpan && !isCurrentUser) {
                nameSpan.addEventListener('click', () => {
                    interactionUsername.textContent = user.display_name;
                    userInteractionModal.dataset.userId = user.id;
                    userInteractionModal.dataset.userName = user.display_name;
                    userInteractionModal.classList.remove('hidden');
                    userInteractionModal.classList.add('flex');
                });
            }
            onlineUsersList.appendChild(userElement);
        });
        addInteractionEvents();
    };

    const addInteractionEvents = () => {
        document.querySelectorAll('.report-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userElement = e.target.closest('li');
                reportUsernameSpan.textContent = userElement.dataset.userName;
                reportUserIdInput.value = userElement.dataset.userId;
                reportModal.classList.remove('hidden');
                reportModal.classList.add('flex');
            });
        });
        document.querySelectorAll('.ban-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const userElement = e.target.closest('li');
                const userId = userElement.dataset.userId;
                const userName = userElement.dataset.userName;
                if (confirm(`Tem a certeza que deseja BANIR permanentemente o utilizador ${userName}?`)) {
                    const formData = new FormData();
                    formData.append('action', 'ban_user');
                    formData.append('user_id', userId);
                    const response = await fetch('/chat/ajax/admin_actions.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.status === 'success') {
                        appendSystemMessage(`${userName} foi banido da sala.`, 'error');
                        fetchRoomState();
                    } else {
                        alert(data.message);
                    }
                }
            });
        });
        document.querySelectorAll('.block-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userElement = e.target.closest('li');
                const userId = parseInt(userElement.dataset.userId, 10);
                if (blockedUserIds.has(userId)) {
                    blockedUserIds.delete(userId);
                    btn.title = 'Bloquear';
                } else {
                    blockedUserIds.add(userId);
                    btn.title = 'Desbloquear';
                }
                renderAllMessages();
            });
        });
    };

    messageForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const content = messageInput.value.trim();
        if (content.length > charLimit) {
            appendSystemMessage(`A sua mensagem excede o limite de ${charLimit} caracteres.`, 'error');
            return;
        }
        if (content) {
            const formData = new FormData();
            formData.append('content', content);
            if (replyInfo) {
                formData.append('recipient_id', replyInfo.id);
                if (replyInfo.type === 'private') formData.append('is_private', 'true');
            }
            try {
                const response = await fetch('/chat/ajax/send_message.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    messageInput.value = '';
                    fetchRoomState();
                } else {
                    appendSystemMessage(data.message, 'error');
                }
            } catch (error) {
                appendSystemMessage('Erro de conexão ao enviar a mensagem.', 'error');
            }
        }
    });

    reportForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(reportForm);
        reportFeedback.textContent = 'A enviar...';
        try {
            const response = await fetch('/chat/ajax/submit_report.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') {
                reportFeedback.style.color = 'green';
                reportFeedback.textContent = data.message;
                setTimeout(() => {
                    reportModal.classList.add('hidden');
                    reportFeedback.textContent = '';
                    reportForm.reset();
                }, 2000);
            } else {
                reportFeedback.style.color = 'red';
                reportFeedback.textContent = data.message;
            }
        } catch (error) {
            reportFeedback.style.color = 'red';
            reportFeedback.textContent = 'Erro de conexão.';
        }
    });

    reportCancelBtn.addEventListener('click', () => {
        reportModal.classList.add('hidden');
        reportFeedback.textContent = '';
        reportForm.reset();
    });

    interactionPublicBtn.addEventListener('click', () => {
        setReplyState({ id: userInteractionModal.dataset.userId, name: userInteractionModal.dataset.userName, type: 'public' });
        userInteractionModal.classList.add('hidden');
    });
    interactionPrivateBtn.addEventListener('click', () => {
        setReplyState({ id: userInteractionModal.dataset.userId, name: userInteractionModal.dataset.userName, type: 'private' });
        userInteractionModal.classList.add('hidden');
    });
    interactionReportBtn.addEventListener('click', () => {
        reportUsernameSpan.textContent = userInteractionModal.dataset.userName;
        reportUserIdInput.value = userInteractionModal.dataset.userId;
        userInteractionModal.classList.add('hidden');
        reportModal.classList.remove('hidden');
        reportModal.classList.add('flex');
    });
    interactionVotekickBtn.addEventListener('click', async () => {
        const targetId = userInteractionModal.dataset.userId;
        const targetName = userInteractionModal.dataset.userName;
        if (confirm(`Tem a certeza que deseja iniciar uma votação para expulsar ${targetName}?`)) {
            const formData = new FormData();
            formData.append('action', 'start_vote');
            formData.append('target_user_id', targetId);
            const response = await fetch('/chat/ajax/votekick.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status !== 'success') alert(data.message);
            userInteractionModal.classList.add('hidden');
        }
    });
    interactionCancelBtn.addEventListener('click', () => userInteractionModal.classList.add('hidden'));

    document.querySelectorAll('.cancel-reply-btn').forEach(btn => btn.addEventListener('click', cancelReply));
    
    if (clearRoomBtn) {
        clearRoomBtn.addEventListener('click', async () => {
            if (confirm('Tem a certeza que deseja apagar TODAS as mensagens desta sala?')) {
                const formData = new FormData();
                formData.append('action', 'clear_room');
                await fetch('/chat/ajax/admin_actions.php', { method: 'POST', body: formData });
            }
        });
    }

    if (leaveRoomBtn) {
        leaveRoomBtn.addEventListener('click', () => {
            navigator.sendBeacon('/chat/ajax/leave_room.php');
            window.location.href = '/rooms';
        });
    }
    
    votekickCastVoteBtn.addEventListener('click', async () => {
        if (currentVoteInfo) {
            const formData = new FormData();
            formData.append('action', 'cast_vote');
            formData.append('vote_id', currentVoteInfo.id);
            const response = await fetch('/chat/ajax/votekick.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status !== 'success') alert(data.message);
        }
    });

    window.addEventListener('beforeunload', () => navigator.sendBeacon('/chat/ajax/leave_room.php'));

    const initializeChat = async () => {
        try {
            const response = await fetch('/chat/ajax/get_last_message_id.php');
            const data = await response.json();
            if (data.status === 'success' && data.last_id) {
                lastMessageId = data.last_id;
            }
        } catch (error) {
            console.error('Erro ao inicializar o chat:', error);
        }
        messagesBox.innerHTML = '';
        setInterval(fetchRoomState, 3000);
        detectAdBlocker();
    };

    initializeChat();
});
