/* ==========================================================
   CLIENT SUPPORT CHAT JS — FINAL FULL VERSION
   ========================================================== */

let messagesLoaded = 0;
let filesToSend = [];
let polling = true;

/* ================= AUTOSCROLL ================= */
function scrollToBottom() {
    const box = document.getElementById("chatBox");
    box.scrollTop = box.scrollHeight;
}

/* ================= LOAD CHAT ================= */
function loadChat(initial = false) {
    const username = document.getElementById("usernameHolder").value;
    if (!username) return;

    fetch(`load_chat_client.php?client=${username}`)
        .then(res => res.json())
        .then(data => {
            if (initial) {
                document.getElementById("chatBox").innerHTML = "";
                messagesLoaded = 0;
            }

            if (data.length > messagesLoaded) {
                const newMsgs = data.slice(messagesLoaded);

                newMsgs.forEach(m => {
                    let attachment = "";
                    if (m.media_path) {
                        attachment = m.media_type === "image"
                            ? `<img src="${m.media_path}" class="chat-img">`
                            : `<video class="chat-img" controls><source src="${m.media_path}"></video>`;
                    }

                    const side = m.sender_type === "client" ? "me" : "csr";

                    const bubble = `
                        <div class="msg-row ${side}">
                            <div class="bubble">
                                ${m.message || ""}
                                ${attachment}
                                <div class="meta">${m.created_at}</div>
                            </div>
                        </div>
                    `;
                    document.getElementById("chatBox").innerHTML += bubble;
                });

                scrollToBottom();
            }

            messagesLoaded = data.length;
        });
}

/* ================= SEND MESSAGE ================= */
function sendMessage() {
    const msg = document.getElementById("message").value.trim();
    const username = document.getElementById("usernameHolder").value;

    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
    fd.append("message", msg);
    fd.append("username", username);

    filesToSend.forEach(f => fd.append("file", f));

    fetch("save_chat_client.php", {
        method: "POST",
        body: fd
    }).then(() => {
        document.getElementById("message").value = "";
        document.getElementById("preview").innerHTML = "";
        filesToSend = [];
        document.getElementById("fileInput").value = "";
        loadChat();
    });
}

/* ================= FILE PREVIEW ================= */
document.getElementById("fileInput").addEventListener("change", e => {
    filesToSend = [...e.target.files];
    document.getElementById("preview").innerHTML = "";

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById("preview").innerHTML +=
                file.type.includes("video")
                    ? `<video src="${ev.target.result}" muted></video>`
                    : `<img src="${ev.target.result}">`;
        };
        reader.readAsDataURL(file);
    });
});

/* ================= EVENT LISTENERS ================= */
document.getElementById("sendBtn").addEventListener("click", sendMessage);
document.getElementById("message").addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
});

/* ================= AUTOPOLL ================= */
setInterval(() => loadChat(false), 1200);

/* Initial */
loadChat(true);
