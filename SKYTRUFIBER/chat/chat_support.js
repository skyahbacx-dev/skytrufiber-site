// ========================================
// SkyTruFiber Client Chat System
// chat_support.js — Full CSR Mirror
// ========================================

let selectedFiles = [];
let lastMessageID = 0;
let loadInterval = null;
const username = new URLSearchParams(window.location.search).get("username");

$(document).ready(function () {

    if (!username) {
        $("#chat-window").html("<p style='padding:20px;text-align:center;color:#888;'>Invalid user.</p>");
        return;
    }

    loadMessages(true);

    // Real-time auto refresh
    loadInterval = setInterval(() => {
        if (!$("#preview-inline").is(":visible")) loadMessages(false);
    }, 1200);

    // Send text
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Upload button
    $("#upload-btn").click(() => $("#chat-upload-media").click());

    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // Lightbox image preview
    $(document).on("click", ".media-thumb", function () {
        $("#lightbox-image").attr("src", $(this).attr("src"));
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").click(() => {
        $("#lightbox-overlay").fadeOut(200);
    });

    // Scroll button
    const box = $("#chat-window");
    const btn = $("#scroll-bottom-btn");

    box.on("scroll", () => {
        const atBottom = box[0].scrollHeight - box.scrollTop() - box.outerHeight() < 50;
        if (atBottom) btn.removeClass("show");
        else btn.addClass("show");
    });

    btn.click(function () {
        scrollToBottom();
        btn.removeClass("show");
    });

});

// Scroll to bottom
function scrollToBottom() {
    const box = $("#chat-window");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 300);
}

// Upload placeholder bubble
function addUploadingPlaceholder() {
    const id = "upload-" + Date.now();
    $("#chat-window").append(`
        <div class="message sent" id="${id}">
            <div class="message-content">
                <div class="message-bubble uploading-bubble">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                </div>
                <div class="message-time">Uploading...</div>
            </div>
        </div>
    `);
    scrollToBottom();
    return id;
}

// LOAD MESSAGES from server
function loadMessages(scrollBottom = false) {

    $.post("load_messages_client.php", { username }, function (html) {

        const incoming = $(html);
        const newID = parseInt(incoming.last().attr("data-msg-id"));

        if (newID > lastMessageID) {
            lastMessageID = newID;
            $("#chat-window").append(incoming);
            if (scrollBottom) scrollToBottom();
        }
    });
}

// SEND MESSAGE
function sendMessage() {
    const msg = $("#message-input").val().trim();

    if (selectedFiles.length > 0) {
        uploadMedia(selectedFiles, msg);
        return;
    }

    if (!msg) return;

    $.post("send_message_client.php", { message: msg, username }, function (res) {
        $("#message-input").val("");
        loadMessages(true);
    }, "json");
}

// FILE PREVIEW BAR
function previewMultiple(files) {
    $("#preview-files").html("");

    files.forEach((file, index) => {

        const removeBtn = `<button class="preview-remove" data-i="${index}">&times;</button>`;

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
        } else if (file.type.startsWith("video")) {
            $("#preview-files").append(`
                <div class="preview-item file-box">🎬 ${file.name}
                    ${removeBtn}
                </div>
            `);
        } else {
            $("#preview-files").append(`
                <div class="preview-item file-box">📎 ${file.name}
                    ${removeBtn}
                </div>
            `);
        }
    });

    $("#preview-inline").slideDown(200);
}

$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});

// UPLOAD MEDIA FILES
function uploadMedia(files, msg = "") {

    const placeholder = addUploadingPlaceholder();

    const fd = new FormData();
    files.forEach(f => fd.append("media[]", f));
    fd.append("message", msg);
    fd.append("username", username);

    $("#preview-inline").slideUp(200);
    selectedFiles = [];
    $("#chat-upload-media").val("");
    $("#message-input").val("");

    $.ajax({
        url: "upload_media_client.php",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: () => {
            $("#" + placeholder).remove();
            loadMessages(true);
        },
        error: err => {
            console.error("Upload error:", err.responseText);
            $("#" + placeholder).remove();
        }
    });
}
