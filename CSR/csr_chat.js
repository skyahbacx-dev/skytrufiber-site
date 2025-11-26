// ================================
// CSR CHAT JAVASCRIPT (FULL FILE)
// ================================

let activeClient = null;
let typingTimeout = null;
const chatMessages = document.getElementById("chatMessages");
const typingIndicator = document.createElement("div");

typingIndicator.className = "typing-notice";
typingIndicator.innerHTML = `<em>Typing...</em>`;
typingIndicator.style.display = "none";
chatMessages.appendChild(typingIndicator);

// ================================
// LOAD CLIENT LIST
// ================================
function loadClientList() {
    $.get("client_list.php", function (data) {
        $("#clientList").html(data);
    });
}

// ================================
// SELECT CLIENT
// ================================
function openChat(clientID, fullname, email, district, brgy) {
    activeClient = clientID;

    document.getElementById("chatName").textContent = fullname;
    document.getElementById("infoName").textContent = fullname;
    document.getElementById("infoEmail").textContent = email;
    document.getElementById("infoDistrict").textContent = district;
    document.getElementById("infoBrgy").textContent = brgy;

    loadChat();
}

// ================================
// LOAD CHAT MESSAGES
// ================================
function loadChat() {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (list) {
        chatMessages.innerHTML = "";

        list.forEach((m) => renderMessage(m));
        chatMessages.appendChild(typingIndicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}

// ================================
// RENDER MESSAGE BUBBLE
// ================================
function renderMessage(m) {
    const row = document.createElement("div");
    row.className = m.sender_type === "csr" ? "msg-row msg-out" : "msg-row msg-in";

    const bubble = document.createElement("div");
    bubble.className = "bubble";

    if (m.message) bubble.appendChild(document.createTextNode(m.message));

    // Media display
    if (m.media && Array.isArray(m.media)) {
        m.media.forEach((file) => {
            if (file.media_type === "image") {
                const img = document.createElement("img");
                img.src = file.media_path;
                img.className = "media-img";
                img.onclick = () => openMediaModal(file.media_path);
                bubble.appendChild(img);
            } else {
                const vid = document.createElement("video");
                vid.src = file.media_path;
                vid.controls = true;
                vid.className = "media-video";
                bubble.appendChild(vid);
            }
        });
    }

    // Timestamp & status
    const t = document.createElement("div");
    t.className = "time";
    t.innerHTML = m.created_at + (m.sender_type === "csr" ? "" :
        m.seen ? " <span class='seen-ic'>✓✓</span>" : " <span class='delivered-ic'>✓</span>");

    bubble.appendChild(t);

    row.appendChild(bubble);
    chatMessages.appendChild(row);
}

// ================================
// SEND MESSAGE
// ================================
$("#sendBtn").click(() => sendMessage());
$("#messageInput").keydown((e) => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

function sendMessage() {
    const message = $("#messageInput").val().trim();
    const fileInput = document.getElementById("fileInput");
    if (!message && fileInput.files.length === 0) return;
    if (!activeClient) return;

    let form = new FormData();
    form.append("client_id", activeClient);
    form.append("sender_type", "csr");
    form.append("message", message);

    for (let file of fileInput.files) {
        form.append("file[]", file);
    }

    fetch("save_chat_csr.php", { method: "POST", body: form })
        .then(res => res.json())
        .then(() => {
            $("#messageInput").val("");
            fileInput.value = "";
            loadChat();
        });
}

// ================================
// TYPING INDICATOR
// ================================
$("#messageInput").on("input", () => {
    if (!activeClient) return;

    fetch("typing_update.php", {
        method: "POST",
        body: new URLSearchParams({ client_id: activeClient, csr_typing: 1 })
    });

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        fetch("typing_update.php", {
            method: "POST",
            body: new URLSearchParams({ client_id: activeClient, csr_typing: 0 })
        });
    }, 1200);
});

// CHECK CLIENT TYPING STATUS
function checkTyping() {
    if (!activeClient) return;
    $.post("check_typing.php", { client_id: activeClient }, function (isTyping) {
        typingIndicator.style.display = isTyping == 1 ? "block" : "none";
    });
}

// ================================
// MEDIA MODAL
// ================================
function openMediaModal(path) {
    document.getElementById("mediaModalContent").src = path;
    document.getElementById("mediaModal").style.display = "flex";
}
document.getElementById("closeMediaModal").onclick = () =>
    document.getElementById("mediaModal").style.display = "none";

// ================================
// INTERVALS
// ================================
setInterval(loadChat, 1200);
setInterval(checkTyping, 1000);
loadClientList();
