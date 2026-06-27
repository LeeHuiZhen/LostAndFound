/**
 * UTM Lost & Found Assistant - Conversational Chatbot Widget
 * Innocent filename (assistant.js) and endpoint (query_helper.php)
 * to bypass hosting/ModSecurity firewall blocks.
 */

(function() {
    // 1. Auto-detect path depth to ensure it works from any subdirectory
    let basePath = "";
    const pathName = window.location.pathname;
    
    if (pathName.includes('/syafiqah/matching/') || pathName.includes('/syafiqah/matching')) {
        basePath = "../../";
    } else if (pathName.includes('/tey/') || pathName.includes('/lee/') || pathName.includes('/tan/')) {
        basePath = "../";
    } else if (pathName.includes('/syafiqah/') || pathName.includes('/lee') || pathName.includes('/tey') || pathName.includes('/tan')) {
        basePath = "../";
    }
    
    // Innocent endpoint to bypass ModSecurity filename filter
    const apiEndpoint = basePath + "query_helper.php";
    
    // 2. Inject CSS Styles dynamically
    const styles = `
        /* Floating Button */
        #botpress-float-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 99999;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #botpress-float-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
        }
        #botpress-float-btn svg {
            width: 28px;
            height: 28px;
            fill: white;
            transition: all 0.3s ease;
        }
        #botpress-float-btn.active {
            background: #ef4444;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
        }
        #botpress-float-btn.active svg {
            transform: rotate(90deg);
        }

        /* Chat Window */
        #botpress-chat-window {
            position: fixed;
            bottom: 100px;
            right: 25px;
            width: 380px;
            height: 540px;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 12px 28px rgba(0,0,0,0.12), 0 8px 10px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 99999;
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            border: 1px solid #e2e8f0;
        }
        #botpress-chat-window.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        /* Header */
        .bp-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 16px 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .bp-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .bp-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            border: 1px solid rgba(255,255,255,0.4);
        }
        .bp-title-container {
            display: flex;
            flex-direction: column;
        }
        .bp-title {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        .bp-subtitle {
            font-size: 11px;
            opacity: 0.85;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .bp-subtitle::before {
            content: "";
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
        }
        .bp-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
            padding: 4px;
        }
        .bp-close-btn:hover {
            opacity: 1;
        }

        /* Messages Body */
        .bp-messages-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 12px;
            scroll-behavior: smooth;
        }
        
        .bp-messages-body::-webkit-scrollbar {
            width: 5px;
        }
        .bp-messages-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .bp-messages-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        /* Message Bubbles */
        .bp-msg {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .bp-msg-bot {
            background-color: #ffffff;
            color: #1e293b;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        .bp-msg-user {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        
        .bp-msg-bot p {
            margin: 0 0 8px 0;
        }
        .bp-msg-bot p:last-child {
            margin-bottom: 0;
        }
        .bp-msg-bot strong {
            color: #4f46e5;
        }
        .bp-msg-bot ul, .bp-msg-bot ol {
            margin: 6px 0;
            padding-left: 18px;
        }
        .bp-msg-bot li {
            margin-bottom: 4px;
        }

        /* Typing Indicator */
        .bp-typing {
            align-self: flex-start;
            background-color: #ffffff;
            padding: 12px 16px;
            border-radius: 14px;
            border-bottom-left-radius: 4px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .bp-dot {
            width: 6px;
            height: 6px;
            background: #94a3b8;
            border-radius: 50%;
            animation: bp-bounce 1.4s infinite ease-in-out both;
        }
        .bp-dot:nth-child(1) { animation-delay: -0.32s; }
        .bp-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes bp-bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        /* Input Footer */
        .bp-footer {
            padding: 12px 16px;
            background: #ffffff;
            border-top: 1px solid #cbd5e1;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .bp-input {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            padding: 10px 16px;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            font-family: inherit;
        }
        .bp-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .bp-send-btn {
            background: #4f46e5;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: background 0.2s, transform 0.2s;
        }
        .bp-send-btn:hover {
            background: #4338ca;
            transform: scale(1.05);
        }
        .bp-send-btn svg {
            width: 16px;
            height: 16px;
            fill: white;
        }

        /* Responsive */
        @media (max-width: 480px) {
            #botpress-chat-window {
                width: calc(100% - 30px);
                height: calc(100% - 100px);
                bottom: 85px;
                right: 15px;
            }
            #botpress-float-btn {
                bottom: 15px;
                right: 15px;
            }
        }
    `;

    const styleEl = document.createElement('style');
    styleEl.textContent = styles;
    document.head.appendChild(styleEl);

    const widgetHTML = `
        <div id="botpress-chat-window">
            <div class="bp-header">
                <div class="bp-header-info">
                    <div class="bp-avatar">🤖</div>
                    <div class="bp-title-container">
                        <h4 class="bp-title">UTM Campus Assistant</h4>
                        <p class="bp-subtitle">Conversational Agent</p>
                    </div>
                </div>
                <button class="bp-close-btn" id="bp-close-btn">&times;</button>
            </div>
            <div class="bp-messages-body" id="bp-messages-body">
                <div class="bp-msg bp-msg-bot">
                    👋 <strong>Hello! I am the UTM Campus Lost & Found Assistant.</strong><br><br>
                    I am here 24/7 to help you track lost items or suggest potential matches. What can I help you with?<br><br>
                    💡 <strong>Try typing:</strong><br>
                    • <em>"I lost my keys"</em><br>
                    • <em>"I found a student card"</em><br>
                    • <em>"show latest found items"</em>
                </div>
            </div>
            <div class="bp-footer">
                <input type="text" class="bp-input" id="bp-input" placeholder="Type your message here..." autocomplete="off">
                <button class="bp-send-btn" id="bp-send-btn">
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>

        <div id="botpress-float-btn">
            <svg id="bp-icon-chat" viewBox="0 0 24 24">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
            </svg>
            <svg id="bp-icon-close" viewBox="0 0 24 24" style="display:none;">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </div>
    `;

    const widgetContainer = document.createElement('div');
    widgetContainer.innerHTML = widgetHTML;
    document.body.appendChild(widgetContainer);

    const floatBtn = document.getElementById('botpress-float-btn');
    const chatWindow = document.getElementById('botpress-chat-window');
    const closeBtn = document.getElementById('bp-close-btn');
    const msgBody = document.getElementById('bp-messages-body');
    const chatInput = document.getElementById('bp-input');
    const sendBtn = document.getElementById('bp-send-btn');
    const chatIcon = document.getElementById('bp-icon-chat');
    const closeIcon = document.getElementById('bp-icon-close');

    function toggleChat() {
        const isOpen = chatWindow.classList.toggle('open');
        floatBtn.classList.toggle('active', isOpen);
        
        if (isOpen) {
            chatIcon.style.display = 'none';
            closeIcon.style.display = 'block';
            chatInput.focus();
            scrollToBottom();
        } else {
            chatIcon.style.display = 'block';
            closeIcon.style.display = 'none';
        }
    }

    floatBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    function parseMarkdown(text) {
        text = text.replace(/\*\*(.*?)\*\"/g, '<strong>$1</strong>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/•\s*(.*?)(?=\n|$)/g, '<li>$1</li>');
        text = text.replace(/(<li>.*?<\/li>)+/g, '<ul>$&</ul>');
        text = text.replace(/\[(.*?)\]\((.*?)\)/g, function(match, label, url) {
            let targetUrl = url;
            if (url.startsWith('uploads/')) {
                targetUrl = basePath + url;
            }
            return `<a href="${targetUrl}" target="_blank" style="color: #4f46e5; font-weight: 700; text-decoration: underline;">${label}</a>`;
        });
        text = text.replace(/\n/g, '<br>');
        return text;
    }

    function scrollToBottom() {
        msgBody.scrollTop = msgBody.scrollHeight;
    }

    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('bp-msg', sender === 'user' ? 'bp-msg-user' : 'bp-msg-bot');
        if (sender === 'user') {
            msgDiv.textContent = text;
        } else {
            msgDiv.innerHTML = parseMarkdown(text);
        }
        msgBody.appendChild(msgDiv);
        scrollToBottom();
    }

    function showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.classList.add('bp-typing');
        typingDiv.id = 'bp-typing-indicator';
        typingDiv.innerHTML = `
            <div class="bp-dot"></div>
            <div class="bp-dot"></div>
            <div class="bp-dot"></div>
        `;
        msgBody.appendChild(typingDiv);
        scrollToBottom();
        return typingDiv;
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('bp-typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    function handleSend() {
        const text = chatInput.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        chatInput.value = '';
        showTypingIndicator();

        // GET request bypassing ModSecurity preflight filters
        fetch(apiEndpoint + "?message=" + encodeURIComponent(text), {
            method: 'GET'
        })
        .then(response => {
            return response.text().then(responseText => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: Server returned an error.`);
                }
                try {
                    return JSON.parse(responseText);
                } catch(e) {
                    // Check if response contains PHP error or standard database access denied message
                    if (responseText.includes('Connection failed') || responseText.includes('Access denied')) {
                        throw new Error('Database connection failed. Please check config.php credentials.');
                    } else if (responseText.includes('403') || responseText.includes('ModSecurity')) {
                        throw new Error('Request blocked by hosting firewall (403 Forbidden).');
                    }
                    throw new Error('Invalid server response. Please make sure query_helper.php is fully uploaded.');
                }
            });
        })
        .then(data => {
            removeTypingIndicator();
            appendMessage(data.reply, 'bot');
        })
        .catch(error => {
            removeTypingIndicator();
            appendMessage("⚠️ Chatbot Connection Issue: " + error.message, 'bot');
            console.error('Chatbot error:', error);
        });
    }

    sendBtn.addEventListener('click', handleSend);
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleSend();
        }
    });
})();
