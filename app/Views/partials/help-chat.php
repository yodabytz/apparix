<!-- Help Chat Widget -->
<div id="helpChatWidget" class="help-chat-widget">
    <!-- Chat Toggle Button -->
    <button id="chatToggle" class="chat-toggle" aria-label="Open help chat">
        <svg class="chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <svg class="close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>

    <!-- Chat Window -->
    <div id="chatWindow" class="chat-window" style="display: none;">
        <div class="chat-header">
            <span>Help &amp; Support</span>
            <button id="chatClose" class="chat-close" aria-label="Close chat">&times;</button>
        </div>

        <div id="chatMessages" class="chat-messages">
            <div class="chat-message bot">
                <div class="message-content">
                    Hi! I'm here to help. You can ask me about:
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        <li>Shipping &amp; delivery</li>
                        <li>Returns &amp; refunds</li>
                        <li>Order status</li>
                        <li>Product questions</li>
                    </ul>
                    <br>Or type your question below!
                </div>
            </div>
        </div>

        <div id="chatInputArea" class="chat-input-area">
            <input type="text" id="chatInput" class="chat-input" placeholder="Type your message..." autocomplete="off">
            <button id="chatSend" class="chat-send" aria-label="Send message">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>

        <!-- Email Support Form (hidden by default) -->
        <div id="emailSupportForm" class="email-support-form" style="display: none;">
            <p class="support-message">I'll connect you with our support team. Please provide your details:</p>
            <input type="email" id="supportEmail" class="support-input" placeholder="Your email address" required>
            <textarea id="supportMessage" class="support-textarea" placeholder="Describe your question or issue..." rows="3" required></textarea>
            <button id="sendSupport" class="support-send-btn">Send to Support</button>
            <button id="backToChat" class="back-to-chat-btn">Back to Chat</button>
        </div>
    </div>
</div>

<style>
.help-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 99999;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.chat-toggle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF68C5 0%, #FF94C8 100%);
    border: 3px solid white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(255, 104, 197, 0.4);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.chat-toggle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 25px rgba(255, 104, 197, 0.5);
}

.chat-toggle svg {
    width: 28px;
    height: 28px;
    color: white;
}

.chat-window {
    position: absolute;
    bottom: 75px;
    right: 0;
    width: 360px;
    max-width: calc(100vw - 40px);
    height: 480px;
    max-height: calc(100vh - 120px);
    background: white;
    border-radius: 16px;
    border: 3px solid white;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-header {
    background: linear-gradient(135deg, #FF68C5 0%, #FF94C8 100%);
    color: white;
    padding: 1rem 1.25rem;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.chat-close:hover { opacity: 1; }

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.chat-message {
    max-width: 85%;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-message.bot { align-self: flex-start; }
.chat-message.user { align-self: flex-end; }

.message-content {
    padding: 0.75rem 1rem;
    border-radius: 16px;
    font-size: 0.9rem;
    line-height: 1.4;
}

.chat-message.bot .message-content {
    background: #f5f5f5;
    color: #333;
    border-bottom-left-radius: 4px;
}

.chat-message.user .message-content {
    background: linear-gradient(135deg, #FF68C5 0%, #FF94C8 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-input-area {
    padding: 1rem;
    border-top: 1px solid #eee;
    display: flex;
    gap: 0.5rem;
}

.chat-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 24px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
}

.chat-input:focus { border-color: #FF68C5; }

.chat-send {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF68C5 0%, #FF94C8 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
}

.chat-send:hover { transform: scale(1.05); }

.chat-send svg {
    width: 20px;
    height: 20px;
    color: white;
}

.email-support-form {
    padding: 1rem;
    border-top: 1px solid #eee;
}

.support-message {
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
    color: #666;
}

.support-input, .support-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    font-family: inherit;
    outline: none;
    box-sizing: border-box;
}

.support-input:focus, .support-textarea:focus { border-color: #FF68C5; }
.support-textarea { resize: none; }

.support-send-btn {
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, #FF68C5 0%, #FF94C8 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.support-send-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 104, 197, 0.3);
}

.back-to-chat-btn {
    width: 100%;
    padding: 0.5rem;
    background: none;
    color: #666;
    border: none;
    font-size: 0.85rem;
    cursor: pointer;
    margin-top: 0.5rem;
}

.back-to-chat-btn:hover { color: #FF68C5; }

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 0.75rem 1rem;
    background: #f5f5f5;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #999;
    border-radius: 50%;
    animation: bounce 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

@media (max-width: 480px) {
    .chat-window {
        width: calc(100vw - 20px);
        right: -10px;
        bottom: 70px;
        height: 60vh;
    }
    .help-chat-widget { bottom: 15px; right: 15px; }
    .chat-toggle { width: 54px; height: 54px; }
}
</style>

<script>
(function() {
    // Move widget to body level to escape stacking context (fixes z-index issues)
    var widget = document.getElementById('helpChatWidget');
    if (widget && widget.parentNode !== document.body) {
        document.body.appendChild(widget);
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Store email for dynamic content
    const storeEmail = '<?php echo escape(storeEmail()); ?>';
    const storeName = '<?php echo escape(appName()); ?>';

    // Predefined safe bot responses (trusted HTML content)
    const botResponses = {
        shipping: '<strong>Shipping Information:</strong><br><br><strong>US Orders:</strong> Standard shipping is typically 5-7 business days.<br><br><strong>International:</strong> 10-21 business days depending on destination.<br><br>Check product pages for specific shipping information.',
        products: '<strong>Our Products:</strong><br><br>Browse our store to discover our full collection. Each product page has detailed descriptions, images, and specifications.<br><br>Use our categories and search to find exactly what you\'re looking for!',
        returns: '<strong>Returns &amp; Refunds:</strong><br><br>We accept returns within 30 days of delivery for unused items in original packaging.<br><br>To start a return, please email us at <strong>' + storeEmail + '</strong> with your order number.<br><br>Refunds are processed within 5-7 business days after we receive the item.',
        order: '<strong>Order Status:</strong><br><br>Once your order ships, you\'ll receive an email with tracking information.<br><br>You can also check your order status by logging into your account or contacting us with your order number.',
        sizing: '<strong>Sizing Help:</strong><br><br>Each product page includes sizing information where applicable.<br><br>If you\'re unsure about sizing, feel free to contact us and we\'ll be happy to help!',
        contact: '<strong>Contact Us:</strong><br><br>Email: <strong>' + storeEmail + '</strong><br><br>Or chat live with our team!<br><br>Would you like me to connect you with a live person now?',
        payment: '<strong>Payment Methods:</strong><br><br>We accept the following payment options:<br><br><strong>Credit/Debit Cards:</strong><br>• Visa<br>• MasterCard<br>• American Express<br>• Discover<br><br><strong>Digital Wallets:</strong><br>• Apple Pay<br>• Google Pay<br>• Link by Stripe<br><br>All payments are processed securely through Stripe. Your card information is encrypted and never stored on our servers.',
        international: '<strong>International Orders:</strong><br><br>Yes! We ship to many countries worldwide.<br><br><strong>Typical Shipping Times:</strong><br>• Canada: 7-14 business days<br>• UK/Europe: 10-18 business days<br>• Australia/NZ: 14-21 business days<br>• Other countries: 14-28 business days<br><br>Please note that customs duties and taxes may apply depending on your country.',
        greeting: 'Hello! How can I help you today? I can answer questions about:<br>• Shipping &amp; delivery<br>• Our products<br>• Payment methods<br>• Returns &amp; refunds<br><br>Just type your question below!',
        thanks: 'You\'re welcome! Is there anything else I can help you with?',
        connectSupport: 'I\'ll connect you with our support team. Please fill out the form below.',
        messageSent: 'Your message has been sent to our support team. We\'ll get back to you within 24 hours. Is there anything else I can help with?',
        default: 'I\'m not sure I understand that question. Here\'s what I can help with:<br><br>• Shipping &amp; delivery times<br>• Our products<br>• Payment methods accepted<br>• Returns &amp; refunds<br><br>Or I can connect you with our support team!'
    };

    const patterns = {
        shipping: ['shipping', 'ship', 'deliver', 'delivery', 'how long', 'when will', 'arrive'],
        products: ['what do you sell', 'what products', 'what items', 'sell', 'catalog', 'collection', 'offerings', 'browse'],
        returns: ['return', 'refund', 'exchange', 'money back', 'send back'],
        order: ['order status', 'track', 'tracking', 'where is my order', 'my order'],
        sizing: ['size', 'sizing', 'fit', 'measurement', 'what size'],
        contact: ['contact', 'email', 'phone', 'reach', 'talk to', 'speak', 'human', 'real person', 'support', 'help me', 'live chat', 'live person', 'agent', 'representative'],
        payment: ['payment', 'pay', 'credit card', 'debit', 'visa', 'mastercard', 'amex', 'american express', 'discover', 'apple pay', 'google pay', 'paypal', 'checkout', 'card', 'cards accepted', 'cards taken'],
        international: ['international', 'outside us', 'canada', 'uk', 'europe', 'australia', 'worldwide', 'overseas', 'abroad']
    };

    const chatToggle = document.getElementById('chatToggle');
    const chatWindow = document.getElementById('chatWindow');
    const chatClose = document.getElementById('chatClose');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatInputArea = document.getElementById('chatInputArea');
    const emailSupportForm = document.getElementById('emailSupportForm');
    const supportEmail = document.getElementById('supportEmail');
    const supportMessage = document.getElementById('supportMessage');
    const sendSupport = document.getElementById('sendSupport');
    const backToChat = document.getElementById('backToChat');
    const chatIcon = chatToggle.querySelector('.chat-icon');
    const closeIcon = chatToggle.querySelector('.close-icon');

    let awaitingContactConfirm = false;

    chatToggle.addEventListener('click', function() {
        const isOpen = chatWindow.style.display !== 'none';
        chatWindow.style.display = isOpen ? 'none' : 'flex';
        chatIcon.style.display = isOpen ? 'block' : 'none';
        closeIcon.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) chatInput.focus();
    });

    chatClose.addEventListener('click', function() {
        chatWindow.style.display = 'none';
        chatIcon.style.display = 'block';
        closeIcon.style.display = 'none';
    });

    function addUserMessage(text) {
        var div = document.createElement('div');
        div.className = 'chat-message user';
        var content = document.createElement('div');
        content.className = 'message-content';
        content.textContent = text; // Safe: user input as text only
        div.appendChild(content);
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function addBotMessage(htmlContent) {
        var div = document.createElement('div');
        div.className = 'chat-message bot';
        var content = document.createElement('div');
        content.className = 'message-content';
        content.innerHTML = htmlContent; // Safe: only predefined trusted content
        div.appendChild(content);
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTyping() {
        var div = document.createElement('div');
        div.className = 'chat-message bot';
        div.id = 'typingIndicator';
        div.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function hideTyping() {
        var typing = document.getElementById('typingIndicator');
        if (typing) typing.remove();
    }

    function getResponse(message) {
        var lowerMsg = message.toLowerCase();

        if (awaitingContactConfirm && (lowerMsg.includes('yes') || lowerMsg.includes('connect') || lowerMsg.includes('support team'))) {
            awaitingContactConfirm = false;
            showEmailSupport();
            return { html: botResponses.connectSupport, showForm: true };
        }

        for (var key in patterns) {
            var matched = patterns[key].some(function(p) { return lowerMsg.includes(p); });
            if (matched) {
                if (key === 'contact') awaitingContactConfirm = true;
                return { html: botResponses[key], showForm: false };
            }
        }

        if (['hi', 'hello', 'hey', 'good morning', 'good afternoon'].some(function(g) { return lowerMsg.includes(g); })) {
            return { html: botResponses.greeting, showForm: false };
        }

        if (['thank', 'thanks', 'appreciate'].some(function(t) { return lowerMsg.includes(t); })) {
            return { html: botResponses.thanks, showForm: false };
        }

        return { html: botResponses.default, showForm: false };
    }

    function sendMessage() {
        var message = chatInput.value.trim();
        if (!message) return;

        addUserMessage(message);
        chatInput.value = '';
        showTyping();

        setTimeout(function() {
            hideTyping();
            var response = getResponse(message);
            addBotMessage(response.html);
        }, 800 + Math.random() * 400);
    }

    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });

    function showEmailSupport() {
        chatInputArea.style.display = 'none';
        emailSupportForm.style.display = 'block';
        supportEmail.focus();
    }

    function hideEmailSupport() {
        emailSupportForm.style.display = 'none';
        chatInputArea.style.display = 'flex';
        chatInput.focus();
    }

    backToChat.addEventListener('click', hideEmailSupport);

    sendSupport.addEventListener('click', function() {
        var email = supportEmail.value.trim();
        var message = supportMessage.value.trim();

        if (!email || !message) {
            alert('Please fill in both your email and message.');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('Please enter a valid email address.');
            return;
        }

        sendSupport.disabled = true;
        sendSupport.textContent = 'Sending...';

        fetch('/api/support-chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, message: message })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                hideEmailSupport();
                supportEmail.value = '';
                supportMessage.value = '';
                addBotMessage(botResponses.messageSent);
            } else {
                alert(data.error || 'Failed to send message. Please try again.');
            }
        })
        .catch(function() {
            alert('Failed to send message. Please email us directly at ' + storeEmail);
        })
        .finally(function() {
            sendSupport.disabled = false;
            sendSupport.textContent = 'Send to Support';
        });
    });
})();
</script>
