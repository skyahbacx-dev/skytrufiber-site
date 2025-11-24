/* ===============================
   GLOBAL VARIABLES
================================ */
let currentClientId = null;
let currentClientName = "";
let selectedFiles = [];

const chatMessages = document.getElementById("chatMessages");
const messageInput = document.getElementById("messageInput");
const fileInput = document.getElementById("fileInput");
const sendBtn = document.getElementById("sendBtn");
const previewArea = document.getElementById("previewArea");

/* ===============================
   LOAD MESSAGES
================================ */
function loadMessages() {
    if (!currentClientId) return;

    fetch(`load_chat_csr.php?client_id=${currentClientId}`)
        .then(res => res.json())
        .then(data => {
            chatMessages.innerHTML = "";
            data.forEach(renderMessage);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
}

/* ===============================
   RENDER MESSAGE BUBBLE
================================ */
function renderMessage(m) {
    const wrap = document.createElement("div");
    wrap.className = (m.sender_type === "csr") ? "message-row outgoing" : "message-row incoming";

    const bubble = document.createElement("div");
    bubble.className = "message-bubble";

    if (m.message) {
        const text = document.createElement("div");
        text.textContent = m.message;
        bubble.appendChild(text);
    }

    if (m.media_path) {
        const ext = m.media_path.split('.').pop().toLowerCase();
        let media;

        if (["jpg","jpeg","png","gif","webp"].includes(ext)) {
            media = document.createElement("img");
            media.src = m.media_path;
            media.className = "chat-image";
        } else {
            media = document.createElement("video");
            media.src = m.media_path;
            media.controls = true;
            media.className = "chat-video";
        }

        bubble.appendChild(media);
    }

    const time = document.createElement("div");
    time.className = "message-time";
    time.textContent = m.created_at;
    bubble.appendChild(time);

    wrap.appendChild(bubble);
    chatMessages.appendChild(wrap);
}

/* ===============================
   SEND MESSAGE
================================ */
function sendMessage() {
    const msg = messageInput.value.trim();

    if (!msg && selectedFiles.length === 0) return;

    const form = new FormData();
    form.append("client_id", currentClientId);
    form.append("message", msg);
    form.append("csr_fullname", CSR_FULLNAME);

    if (selectedFiles.length > 0) {
        form.append("file", selectedFiles[0]); // Only upload 1 file
    }

    fetch("save_chat_csr.php", { method: "POST", body: form })
        .then(r => r.json())
        .then(res => {
            if (res.status === "ok") {
                messageInput.value = "";
                fileInput.value = "";
                selectedFiles = [];
                previewArea.innerHTML = "";  // Clear preview
                loadMessages();
            }
        });
}

/* ===============================
   HANDLE FILE SELECT & PREVIEW
================================ */
fileInput.addEventListener("change", () => {
    if (!fileInput.files.length) return;

    selectedFiles = [ fileInput.files[0] ];
    const file = selectedFiles[0];
    const preview = document.createElement("div");
    preview.className = "preview-box";

    const ext = file.name.split('.').pop().toLowerCase();

    if (["jpg","jpeg","png","gif","webp"].includes(ext)) {
        const img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        img.className = "preview-media";
        preview.appendChild(img);
    } else {
        const video = document.createElement("video");
        video.src = URL.createObjectURL(file);
        video.controls = true;
        video.className = "preview-media";
        preview.appendChild(video);
    }

    previewArea.innerHTML = "";
    previewArea.appendChild(preview);
});

/* ===============================
   ENTER TO SEND
================================ */
messageInput.addEventListener("keydown", e => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

/* ===============================
   SELECT CLIENT
================================ */
function selectClient(id, name, csr) {
    currentClientId = id;
    currentClientName = name;

    document.getElementById("chatName").textContent = name;
    loadMessages();
}

/* ===============================
   REFRESH LOOP
================================ */
setInterval(loadMessages, 1500);
