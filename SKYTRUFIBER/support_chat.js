/* =======================================================
   CLIENT CHAT SIDE — FINAL JS
   ======================================================= */

let filesToSend = [];
let lastCount = 0;
let loading = false;

/* ================= LOAD MESSAGES ================= */
function loadMessages(initial = false) {
    if (loading) return;
    loading = true;

    $.getJSON("load_chat_client.php?client=" + encodeURIComponent(clientUsername), function(messages) {

        if (initial) {
            $("#chatMessages").html("");
            lastCount = 0;
        }

        if (messages.length > lastCount) {
            const newMsgs = messages.slice(lastCount);

            newMsgs.forEach((msg, idx) => {

                const sender = msg.sender_type === "csr" ? "csr" : "client";
                let attachment = "";

                if (msg.media_path) {
                    if (msg.media_type === "image") {
                        attachment = `<img src="${msg.media_path}" class="file-img" onclick="openMedia('${msg.media_path}')">`;
                    } else {
                        attachment = `<video class="file-img" controls><source src="${msg.media_path}"></video>`;
                    }
                }

                let statusHTML = "";
                if (msg.sender_type === "client") {
                    if (msg.seen) statusHTML = `<span class="tick blue">✓✓</span>`;
                    else statusHTML = `<span class="tick">✓</span>`;
                }

                const html = `
                <div class="msg-row ${sender}">
                    <div class="bubble">${msg.message || ""}${attachment}</div>
                    <div class="meta">${msg.created_at} ${statusHTML}</div>
                </div>`;

                $("#chatMessages").append(html);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastCount = messages.length;
        loading = false;
    });
}

/* ================= SEND MSG ================= */
$("#sendBtn").click(sendMessage);
$("#messageInput").on("keypress", (e) => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let message = $("#messageInput").val().trim();
    if (!message && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("username", clientUsername);
    fd.append("message", message);

    filesToSend.forEach(f => fd.append("file[]", f));

    $.ajax({
        url: "save_chat_client.php",
        method: "POST",
        processData: false,
        contentType: false,
        data: fd,
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages(false);
        }
    });
}

/* ================= MEDIA UPLOAD PREVIEW ================= */
$("#fileInput").on("change", (e) => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = (ev) => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`}
                </div>`);
        };
        reader.readAsDataURL(file);
    });
});

/* ================= IMAGE VIEWER ================= */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ================= AUTO REFRESH ================= */
setInterval(() => loadMessages(false), 1200);

/* INITIAL */
loadMessages(true);
