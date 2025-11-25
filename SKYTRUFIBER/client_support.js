const chatMessages = document.getElementById("chatMessages");
const messageInput = document.getElementById("messageInput");
const sendBtn = document.getElementById("sendBtn");
const previewArea = document.getElementById("previewArea");
const fileInput = document.getElementById("fileInput");

function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function loadMessages() {
    fetch("load_chat_client.php")
        .then(response => response.json())
        .then(data => {
            chatMessages.innerHTML = "";
            let lastDate = "";

            data.forEach(msg => {
                const date = new Date(msg.created_at).toLocaleDateString();
                if (date !== lastDate) {
                    chatMessages.innerHTML += `<div class="date-separator">${date}</div>`;
                    lastDate = date;
                }

                let row = document.createElement("div");
                row.classList.add("msg-row", msg.sender_type);

                let bubble = document.createElement("div");
                bubble.classList.add("bubble");
                bubble.textContent = msg.message;

                row.appendChild(bubble);
                chatMessages.appendChild(row);
            });

            scrollToBottom();
        });
}

function sendMessage(text) {
    const formData = new FormData();
    formData.append("message", text);

    fetch("save_chat_client.php", {
        method: "POST",
        body: formData
    }).then(() => {
        messageInput.value = "";
        loadMessages();
    });
}

sendBtn.addEventListener("click", () => {
    const message = messageInput.value.trim();
    if (message.length > 0) sendMessage(message);
});

messageInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (message.length > 0) sendMessage(message);
    }
});

// Load messages every 2 seconds
setInterval(loadMessages, 2000);
loadMessages();
