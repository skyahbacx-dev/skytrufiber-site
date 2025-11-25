let selectedClient = null;
let lastMessageId = 0;
let typingTimeout = null;

// Load client list immediately
loadClients();

// ---------------- LOAD CLIENT LIST ----------------
function loadClients() {
    fetch("client_list.php")
        .then(res => res.text())
        .then(html => {
            document.getElementById("clientList").innerHTML = html;
        })
        .catch(err => console.error("Client load error:", err));
}

// ---------------- SELECT CLIENT ----------------
function openChat(clientId, name) {
    selectedClient = clientId;
    document.getElementById("chatName").textContent = name;
    document.getElementById("chatMessages").innerHTML = "";
    lastMessageId = 0;

    loadMessages();
    startRealtime();
}

// ---------------- LOAD MESSAGES ----------------
function loadMessages() {
    if (!selectedClient) return;

    fetch(`load_chat_csr.php?client_id=${selectedClient}&last_id=${lastMessageId}`)
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                data.forEach(msg => appendMessage(msg));
                lastMessageId = data[data.length - 1].id;
                scrollToBottom();
            }
        })
        .catch(err => console.error("Message load error:", err));
}

// ---------------- REALTIME REFRESH ----------------
function startRealtime() {
    setInterval(loadMessages, 1500);
}

// ---------------- SEND MESSAGE ----------------
document.getElementById("sendBtn").addEventListener("click", sendMessage);
document.getElementById("messageInput").addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    const text = document.getElementById("messageInput").value.trim();
    if (!text || !selectedClient) return;

    const formData = new FormData();
    formData.append("client_id", selectedClient);
    formData.append("message", text);
    formData.append("sender_type", "CSR");

    fetch("save_chat_csr.php", { method: "POST", body: formData })
        .then(res => res.text())
        .then(() => {
            document.getElementById("messageInput").value = "";
            loadMessages();
        })
        .catch(err => console.error("Send error:", err));
}

// ---------------- FILE UPLOAD ----------------
document.getElementById("fileInput").addEventListener("change", async function () {
    if (!selectedClient) return;

    for (const file of this.files) {
        await uploadFile(file);
    }
    this.value = "";
});

async function uploadFile(file) {
    const signed = await fetch("upload/sign.php").then(res => res.json());

    const uploadUrl = signed.uploadUrl;
    const authToken = signed.authToken;

    const b2form = new FormData();
    b2form.append("file", file);

    const response = await fetch(uploadUrl, {
        method: "POST",
        headers: { "Authorization": authToken },
        body: b2form
    });

    const result = await response.json();
    const mediaUrl = `https://s3.us-east-005.backblazeb2.com/ahba-chat-media/${result.fileName}`;

    const formData = new FormData();
    formData.append("client_id", selectedClient);
    formData.append("media_path", mediaUrl);
    formData.append("sender_type", "CSR");

    fetch("save_chat_media.php", { method: "POST", body: formData })
        .then(() => loadMessages());
}

// ---------------- DISPLAY MESSAGE ----------------
function appendMessage(msg) {
    const container = document.getElementById("chatMessages");
    const bubble = document.createElement("div");

    bubble.className = msg.sender_type === "CSR" ? "bubble me" : "bubble them";

    if (msg.media_path) {
        bubble.innerHTML = `<img src="${msg.media_path}" class="chat-image" onclick="openMedia('${msg.media_path}')">`;
    } else {
        bubble.innerHTML = `<p>${msg.message}</p>`;
    }

    container.appendChild(bubble);
}

// ---------------- MEDIA VIEWER ----------------
function openMedia(src) {
    document.getElementById("mediaModalContent").src = src;
    document.getElementById("mediaModal").style.display = "flex";
}
document.getElementById("closeMediaModal").onclick = () => {
    document.getElementById("mediaModal").style.display = "none";
};

// ---------------- AUTO-SCROLL ----------------
function scrollToBottom() {
    const chat = document.getElementById("chatMessages");
    chat.scrollTop = chat.scrollHeight;
}

// Search filter
document.getElementById("searchInput").addEventListener("keyup", () => {
    const q = document.getElementById("searchInput").value.toLowerCase();
    document.querySelectorAll(".client-entry").forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? "" : "none";
    });
});
