// ========================================
// SkyTruFiber Client Chat
// chat_support.js — Full Version
// ========================================

let selectedFiles = [];
let lastMessageID = 0;
let messageInterval = null;

// INIT
$(document).ready(function () {
    loadMessages(true);

    messageInterval = setInterval(() => {
        if (!$("#preview-inline").is(":visible")) loadMessages(false);
    }, 1500);

    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
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

    // Lightbox Controls
    $(document).on("click", ".media-thumb", function () {
        const src = $(this).attr("src");
        $("#lightbox-image").attr("src", src);
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-overlay, #lightbox-close").click(() => {
        $("#lightbox-overlay").fadeOut(200);
    });

    // Scroll Button
    const chatBox = $("#chat-messages");
    const scrollBtn = $("#scroll-bottom-btn");

    chatBox.on("scroll", function () {
        const atBottom = chatBox[0].scrollHeight - chatBox.scrollTop() - chatBox.outerHeight() < 50;
        if (atBottom) scrollBtn.removeClass("show");
        else scrollBtn.addClass("show");
    });
    scrollBtn.click(() => scrollToBottomSmooth());
});


// ========================================
// Smooth Scroll
// ========================================
function scrollToBottomSmooth() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 300);
}


// ========================================
// Upload Placeholder
// ========================================
function addUploadingPlaceholder() {
    const tempID = "uploading-" + Date.now();

    $("#chat-messages").append(`
        <div class="message sent" id="${tempID}">
            <div class="message-bubble uploading-bubble">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
            <div class="message-time">Uploading...</div>
        </div>
    `);

    scrollToBottomSmooth();
    return tempID;
}


// ========================================
// Load Messages
// ========================================
function loadMessages(scrollBottom = false) {
    $.post("chat/load_messages_client.php", function (html) {
        const block = $(html);
        const newLastID = parseInt(block.last().attr("data-msg-id"));

        if (newLastID > lastMessageID) {
            lastMessageID = newLastID;
            $("#chat-messages").append(block);

            if (scrollBottom) scrollToBottomSmooth();
        }
    });
}


// ========================================
// PREVIEW BAR
// ========================================
function previewMultiple(files) {
    $("#preview-files").html("");

    files.forEach((file, index) => {
        const removeBtn = `<button class="preview-remove" onclick="removePreview(${index})">&times;</button>`;

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
        }
        else if (file.type.startsWith("video")) {
            $("#preview-files").append(`
                <div class="preview-item">
                    <video class="preview-thumb" muted autoplay loop>
                        <source src="${URL.createObjectURL(file)}">
                    </video>
                    ${removeBtn}
                </div>
            `);
        }
        else {
            $("#preview-files").append(`
                <div class="preview-item file-box">
                    <span>${file.name}</span>
                    ${removeBtn}
                </div>
            `);
        }
    });

    $("#preview-inline").slideDown(200);
}


// ========================================
// Remove File From Preview
// ========================================
function removePreview(index) {
    selectedFiles.splice(index, 1);
    if (selectedFiles.length === 0) {
        $("#preview-inline").slideUp(200);
    } else {
        previewMultiple(selectedFiles);
    }
}


// ========================================
// SEND TEXT or MEDIA
// ========================================
function sendMessage() {
    const msg = $("#chat-input").val().trim();

    if (selectedFiles.length > 0) {
        uploadMedia(selectedFiles, msg);
        return;
    }

    if (!msg) return;

    $.post("chat/send_message_client.php", {
        message: msg,
        sender_type: "client"
    }, function (res) {
        if (res.status === "ok") {
            $("#chat-input").val("");
            loadMessages(true);
        }
    }, "json");
}


// ========================================
// MEDIA UPLOAD
// ========================================
function uploadMedia(files, msg = "") {
    const placeholderID = addUploadingPlaceholder();
    const fd = new FormData();

    files.forEach(f => fd.append("media[]", f));
    fd.append("message", msg);

    $("#preview-inline").slideUp(200);
    selectedFiles = [];
    $("#chat-upload-media").val("");
    $("#chat-input").val("");

    $.ajax({
        url: "chat/upload_media_client.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        dataType: "json",

        success: res => {
            $("#" + placeholderID).remove();
            loadMessages(true);
        },
        error: err => {
            console.error("Upload Error:", err.responseText);
            $("#" + placeholderID).remove();
        }
    });
}
