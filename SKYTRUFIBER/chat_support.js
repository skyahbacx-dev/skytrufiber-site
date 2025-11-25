/* =======================================================
   CLIENT CHAT JS — FINAL BUILD (Batch 8)
   ======================================================= */

let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

const username = new URLSearchParams(window.location.search).get("username");

/* ============ LOAD MESSAGES ================= */
function loadMessages(initial = false) {
    if (!username || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_client.php?client=" + username, function (messages) {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMessages = messages.slice(lastMessageCount);

            newMessages.forEach(m => {
                const side = (m.sender_type === "client") ? "client" : "csr";

                let attachment = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        attachment =
                            `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else {
                        attachment =
                            `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message ?? ""}${attachment}</div>
                            <div class="meta">${m.created_at}</div>
                        </div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/* ============ SEND MESSAGE ================= */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("username", username);
    fd.append("message", msg);

    filesToSend.forEach(file => fd.append("file", file));

    $.ajax({
        url: "save_chat_client.php",
        method: "POST",
        data: fd,
        contentType: false,
        processData: false,
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
}

/* ============ FILE PREVIEW ================= */
$("#fileInput").change(e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const r = new FileReader();
        r.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        r.readAsDataURL(file);
    });
});

/* ============ MEDIA VIEWER ================= */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ============ AUTO REFRESH ================= */
setInterval(() => loadMessages(false), 1500);

/* INITIAL LOAD */
loadMessages(true);
