/* =======================================================
   SUPPORT CHAT JS — MESSENGER STYLE (WITH AVATARS)
   ======================================================= */

document.addEventListener("DOMContentLoaded", () => {
    const chatBody     = document.getElementById("chatBody");
    const messageInput = document.getElementById("messageInput");
    const fileInput    = document.getElementById("fileInput");
    const sendBtn      = document.getElementById("sendBtn");

    const mediaViewer  = document.getElementById("mediaViewer");
    const viewerImage  = document.getElementById("viewerImage");
    const viewerClose  = document.getElementById("viewerClose");

    const previewArea  = document.getElementById("previewArea");

    let polling = null;

    /* =============== LOAD CHAT =============== */
    function loadChat() {
        fetch("load_chat_client.php?client=" + encodeURIComponent(username))
            .then(r => r.json())
            .then(list => {
                chatBody.innerHTML = "";

                let lastDate = "";
                list.forEach(m => {
                    // Optional simple day label based on first 6 chars: "Jan 02"
                    const dayLabel = (m.created_at || "").substring(0, 6);
                    if (dayLabel && dayLabel !== lastDate) {
                        const d = document.createElement("div");
                        d.className = "date-line";
                        d.textContent = dayLabel;
                        chatBody.appendChild(d);
                        lastDate = dayLabel;
                    }

                    chatBody.appendChild(buildMessageRow(m));
                });

                chatBody.scrollTop = chatBody.scrollHeight;
            })
            .catch(err => console.error("loadChat error:", err));
    }

    /* =============== BUILD MESSAGE ROW =============== */
    function buildMessageRow(m) {
        const isCSR = (m.sender_type === "csr");
        const row = document.createElement("div");
        row.className = "msg " + (isCSR ? "support" : "client");

        // Avatar (default avatar for both sides)
        const avatar = document.createElement("img");
        avatar.src = "default-avatar.png";
        avatar.className = "avatar";

        const bubble = document.createElement("div");
        bubble.className = "bubble";

        // Text
        if (m.message) {
            const textNode = document.createTextNode(m.message);
            bubble.appendChild(textNode);
        }

        // Attachment
        if (m.media_path) {
            if (m.media_type === "image") {
                const img = document.createElement("img");
                img.src = m.media_path;
                img.className = "attach-img";
                img.onclick = () => openViewer(m.media_path);
                bubble.appendChild(document.createElement("br"));
                bubble.appendChild(img);
            } else {
                const vid = document.createElement("video");
                vid.src = m.media_path;
                vid.controls = true;
                vid.style.width = "240px";
                vid.style.marginTop = "8px";
                bubble.appendChild(document.createElement("br"));
                bubble.appendChild(vid);
            }
        }

        // Meta (time + ticks)
        const meta = document.createElement("div");
        meta.className = "meta";
        meta.textContent = m.created_at || "";

        // ticks for CLIENT only (what client sees)
        if (!isCSR) {
            const span = document.createElement("span");
            span.classList.add("tick");
            if (m.seen) {
                span.classList.add("blue");
                span.textContent = "✓✓";
            } else {
                span.textContent = "✓";
            }
            meta.appendChild(span);
        }

        bubble.appendChild(document.createElement("br"));
        bubble.appendChild(meta);

        // Order: client side | support side (avatar on correct side)
        if (isCSR) {
            // Support on right: bubble first, avatar after
            row.appendChild(bubble);
            row.appendChild(avatar);
        } else {
            // Client on left: avatar first, bubble after
            row.appendChild(avatar);
            row.appendChild(bubble);
        }

        return row;
    }

    /* =============== SEND MESSAGE =============== */
    function sendMessage() {
        const msg = (messageInput.value || "").trim();
        const files = fileInput.files;

        if (!msg && (!files || files.length === 0)) return;

        const form = new FormData();
        form.append("sender_type", "client");
        form.append("message", msg);
        form.append("username", username);

        // Backend save_chat_client.php expects a single 'file'
        if (files && files.length > 0) {
            form.append("file", files[0]);
        }

        fetch("save_chat_client.php", {
            method: "POST",
            body: form
        })
        .then(r => r.json())
        .then(res => {
            messageInput.value = "";
            fileInput.value = "";
            previewArea.innerHTML = "";
            loadChat();
        })
        .catch(err => console.error("sendMessage error:", err));
    }

    sendBtn.addEventListener("click", sendMessage);

    messageInput.addEventListener("keydown", e => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    /* =============== FILE PREVIEW =============== */
    fileInput.addEventListener("change", () => {
        previewArea.innerHTML = "";
        const file = fileInput.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = e => {
            // Only show preview if image or video
            if (file.type.startsWith("image/")) {
                const img = document.createElement("img");
                img.src = e.target.result;
                img.style.maxWidth = "120px";
                img.style.borderRadius = "10px";
                img.style.display = "block";
                previewArea.appendChild(img);
            } else if (file.type.startsWith("video/")) {
                const vid = document.createElement("video");
                vid.src = e.target.result;
                vid.controls = true;
                vid.style.maxWidth = "120px";
                previewArea.appendChild(vid);
            }
        };
        reader.readAsDataURL(file);
    });

    /* =============== MEDIA VIEWER =============== */
    function openViewer(src) {
        viewerImage.src = src;
        mediaViewer.classList.add("show");
    }

    viewerClose.addEventListener("click", () => {
        mediaViewer.classList.remove("show");
        viewerImage.src = "";
    });

    // expose to HTML inline calls if needed
    window.openViewer = openViewer;

    /* =============== POLLING =============== */
    loadChat();
    polling = setInterval(loadChat, 1500);
});
