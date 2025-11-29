// ========================================
// SkyTruFiber Client Chat — Frontend
// Full Media Support + Preview + BLOB Streaming
// ========================================

let selectedFiles = [];
let lastMessageID = 0;

$(document).ready(function () {

    loadMessages(true);
    setInterval(() => { loadMessages(false); }, 1200);

    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    $("#upload-btn").click(() => $("#chat-upload-media").click());

    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    $(document).on("click", ".media-thumb", function () {
        $("#lightbox-image").attr("src", $(this).attr("src"));
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-overlay, #lightbox-close").click(() => {
        $("#lightbox-overlay").fadeOut(200);
    });

});


// ========================================
// Load Messages
// ========================================
function loadMessages(forceBottom = false) {

    $.post("../chat/load_messages_client.php", function (html) {

        const $incoming = $(html);
        const latestID = parseInt($incoming.last().attr("data-msg-id"));

        if (latestID > lastMessageID) {
            lastMessageID = latestID;
            $("#chat-window").append($incoming);
            if (forceBottom) scrollToBottom();
        }

    });
}

function scrollToBottom() {
    $("#chat-window").stop().animate({ scrollTop: $("#chat-window")[0].scrollHeight }, 400);
}


// ========================================
// Sending Text or Media
// ========================================
function sendMessage() {

    const msg = $("#message-input").val().trim();

    if (selectedFiles.length > 0) {
        uploadMedia(selectedFiles, msg);
        return;
    }

    if (!msg) return;

    $.post("../chat/send_message_client.php", { message: msg }, function (res) {
        if (res.status === "ok") {
            $("#message-input").val("");
            loadMessages(true);
        }
    }, "json");
}


// ========================================
// Preview Selected Media Before Upload
// ========================================
function previewMultiple(files) {

    $("#preview-files").html("");

    files.forEach((file, index) => {

        const removeBtn = `<button class="preview-remove" data-index="${index}">&times;</button>`;

        if (file.type.startsWith("image")) {
            const reader = new FileReader();
            reader.onload = e => {
                $("#preview-files").append(`
                    <div class="preview-item">
                        <img src="${e.target.result}" class="preview-thumb">
                        ${removeBtn}
                    </div>
                `);
            };
            reader.readAsDataURL(file);

        } else {
            $("#preview-files").append(`
                <div class="preview-item file-box">
                    ${file.name}
                    ${removeBtn}
                </div>
            `);
        }
    });

    $("#preview-inline").slideDown(200);
}


// ========================================
// Upload Media (BLOB)
// ========================================
function uploadMedia(files, msg = "") {

    const fd = new FormData();
    files.forEach(f => fd.append("media[]", f));
    fd.append("message", msg);

    $("#preview-inline").slideUp(200);
    selectedFiles = [];
    $("#chat-upload-media").val("");
    $("#message-input").val("");

    $.ajax({
        url: "../chat/upload_media_client.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        dataType: "json",
        success: res => { loadMessages(true); },
        error: err => { console.error(err.responseText); }
    });
}
