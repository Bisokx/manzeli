/* ============================================================
   MANZELI CHATBOT — Frontend Logic
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const bubble = document.getElementById('chatbotBubble');
    const window_ = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotClose');
    const input = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');
    const messagesContainer = document.getElementById('chatbotMessages');
    const typingIndicator = document.getElementById('chatbotTyping');
    const quickActions = document.querySelectorAll('.chatbot-quick-btn');

    let isOpen = false;
    let isLoading = false;
    let hasGreeted = false;

    // Toggle chat window
    bubble.addEventListener('click', function () {
        isOpen = !isOpen;
        window_.classList.toggle('open', isOpen);
        bubble.classList.toggle('active', isOpen);

        if (isOpen) {
            input.focus();
            // Show welcome message on first open
            if (!hasGreeted) {
                addBotMessage("مرحبا! Welcome to Manzeli 🏠 I'm your AI assistant. I can help you find properties, book rentals, or answer questions about the platform. How can I help?");
                hasGreeted = true;
            }
        }
    });

    // Close button inside header
    closeBtn.addEventListener('click', function () {
        isOpen = false;
        window_.classList.remove('open');
        bubble.classList.remove('active');
    });

    // Close on click outside
    document.addEventListener('click', function (e) {
        if (isOpen && !window_.contains(e.target) && !bubble.contains(e.target)) {
            isOpen = false;
            window_.classList.remove('open');
            bubble.classList.remove('active');
        }
    });

    // Send message
    sendBtn.addEventListener('click', sendMessage);

    // Enter to send (Shift+Enter for new line)
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize input
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });

    // Quick action buttons
    quickActions.forEach(function (btn) {
        btn.addEventListener('click', function () {
            input.value = this.dataset.message;
            sendMessage();
        });
    });

    function sendMessage() {
        const message = input.value.trim();
        if (!message || isLoading) return;

        // Add user message to chat
        addUserMessage(message);

        // Clear input
        input.value = '';
        input.style.height = 'auto';

        // Show typing indicator
        showTyping();

        // Send to backend
        isLoading = true;
        sendBtn.disabled = true;

        fetch(CHATBOT_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            hideTyping();
            if (data.success) {
                addBotMessage(data.message);
            } else {
                addBotMessage(data.error || 'Sorry, something went wrong. Please try again.');
            }
        })
        .catch(function () {
            hideTyping();
            addBotMessage('Unable to connect. Please check your internet and try again.');
        })
        .finally(function () {
            isLoading = false;
            sendBtn.disabled = false;
            input.focus();
        });
    }

    function addUserMessage(text) {
        var div = document.createElement('div');
        div.className = 'chatbot-msg user';
        div.textContent = text;
        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function addBotMessage(text) {
        var div = document.createElement('div');
        div.className = 'chatbot-msg bot';
        // Basic formatting: convert **text** to bold, and newlines to <br>
        var formatted = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
        div.innerHTML = formatted;
        messagesContainer.appendChild(div);
        scrollToBottom();

        // Hide quick actions after first interaction
        var quickActionsContainer = document.querySelector('.chatbot-quick-actions');
        if (quickActionsContainer) {
            quickActionsContainer.style.display = 'none';
        }
    }

    function showTyping() {
        typingIndicator.classList.add('show');
        scrollToBottom();
    }

    function hideTyping() {
        typingIndicator.classList.remove('show');
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});
