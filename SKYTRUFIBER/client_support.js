let filesToSend = [];
let lastMessageCount = 0;

function loadMessages(initial = false) {
    $.getJSON("load_chat_client.php", messages => {

        if (initial) {
            $("#clientMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";

                let attachment = "";
                if (m.media_path) {
                    attachment = m.media_type === "image"
                        ? `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`
                        : `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                }

                const html = `
                <div class="msg-row ${side}">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${attachment}</div>
                        <div class="meta">${m.created_at}</div>
                    </div>
                </div>`;

                $("#clientMessages").append(html);
            });

            $("#clientMessages").scrollTop($("#clientMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);

    filesToSend.forEach(f => fd.append("media", f));

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

$(".file-upload-icon").click(() => $("#fileInput").click());

$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

setInterval(() => loadMessages(false), 1200);

loadMessages(true);
