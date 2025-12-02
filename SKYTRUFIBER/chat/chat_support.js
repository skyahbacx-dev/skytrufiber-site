// ========================================
// SkyTruFiber Client Chat System
// chat_support.js — Instant DOM + Thumb Blob + Fast Upload + Gallery + Swipe + Emoji Popup
// ========================================

let selectedFiles = [];
let lastMessageID = 0;
let loadInterval = null;
let galleryItems = [];
let currentIndex = 0;

const username = new URLSearchParams(window.location.search).get("username");

$(document).ready(function () {

    if (!username) {
        $("#chat-messages").html("<p style='padding:20px;text-align:center;color:#888;'>Invalid user.</p>");
        return;
    }

    // Fade animation for first load
    $("#chat-messages").css({ opacity: 0, transform: "translateY(20px)" });
    setTimeout(() => {
        $("#chat-messages").css({
            opacity: 1,
            transform: "translateY(0)",
            transition: "all .45s ease"
        });
    }, 150);

    loadMessages(true);

    // Refresh CSR messages only
    loadInterval = setInterval(() => {
        if (!$("#preview-inline").is(":visible")) loadMessages(false);
    }, 1200);

    // Send message
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) { e.preventDefault(); sendMessage(); }
    });

    // Upload select
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // Lightbox
    $(document).on("click", ".media-thumb, .media-video", function () {
        const group = $(this).closest(".message");
        const media = group.find(".media-thumb, .media-video");

        galleryItems = [];
        media.each(function () {
            galleryItems.push({
                thumb: $(this).attr("src"),
                full: $(this).attr("data-full"),
                type: $(this).is("img") ? "image" : "video"
            });
        });

        currentIndex = media.index(this);
        openLightbox(currentIndex);
    });

    $("#lightbox-next").click(nextLightbox);
    $("#lightbox-prev").click(prevLightbox);

    $("#lightbox-close, #lightbox-overlay").click(e => {
        if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close")
            $("#lightbox-overlay").fadeOut(200);
    });

    // Swipe support
    let startX = 0;
    document.getElementById("lightbox-overlay").addEventListener("touchstart", e => {
        startX = e.changedTouches[0].clientX;
    });

    document.getElementById("lightbox-overlay").addEventListener("touchend", e => {
        const endX = e.changedTouches[0].clientX;
        if (endX < startX - 50) nextLightbox();
        if (endX > startX + 50) prevLightbox();
    });

    // Auto-scroll button
    const box = $("#chat-messages");
    const btn = $("#scroll-bottom-btn");

    box.on("scroll", () => {
        const atBottom = box[0].scrollHeight - box.scrollTop() - box.outerHeight() < 50;
        if (atBottom) btn.removeClass("show");
        else btn.addClass("show");
    });

    btn.click(() => {
        scrollToBottom();
        btn.removeClass("show");
    });

    // Logout
    $(document).on("click", "#logout-btn", () => window.location.href = "logout.php");

    // ===== EMOJI POPUP =====
    $("#emoji-btn").click(() => {
        $("#emoji-popup").toggleClass("show");
    });

    // Insert emoji
    $(document).on("click", ".emoji-option", function () {
        const emoji = $(this).text();
        const input = $("#message-input");
        const cursor = input.prop("selectionStart");
        const text = input.val();
        input.val(text.substring(0, cursor) + emoji + text.substring(cursor));
        input.focus();
    });

    // Close when clicking outside
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#emoji-popup, #emoji-btn").length) {
            $("#emoji-popup").removeClass("show");
        }
    });

});


// ===== INSTANT DOM MESSAGE =====
function appendClientMessageInstant(msg) {
    $("#chat-messages").append(`
        <div class="message sent fadeup">
            <div class="message-avatar"><img src="/upload/default-avatar.png"></div>
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
                <div class="message-time">now</div>
            </div>
        </div>
    `);

    scrollToBottom();
}


// ===== LOAD MESSAGES =====
function loadMessages(scrollBottom = false) {
    $.post("load_messages_client.php", { username }, html => {
        const incoming = $(html);
        if (!incoming.length) return;

        const newID = parseInt(incoming.last().attr("data-msg-id")) || 0;
        if (newID > lastMessageID) {
            lastMessageID = newID;
            $("#chat-messages").append(incoming.addClass("fadeup"));
            if (scrollBottom) scrollToBottom();
        }
    });
}


// ===== SEND =====
function sendMessage() {
    const msg = $("#message-input").val().trim();
    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);
    if (!msg) return;

    appendClientMessageInstant(msg);
    $.post("send_message_client.php", { message: msg, username }, () => {
        $("#message-input").val("");
    }, "json");
}


// ===== PREVIEW UPLOAD =====
function previewMultiple(files) {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(200);

    files.forEach((file, index) => {
        const remove = `<button class="preview-remove" data-i="${index}">&times;</button>`;
        const thumb = file.type.startsWith("image")
            ? `<img src="${URL.createObjectURL(file)}" class="preview-thumb">`
            : `<div class="file-box">📎 ${file.name}</div>`;

        $("#preview-files").append(`<div class="preview-item">${thumb}${remove}</div>`);
    });
}


// Remove preview
$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});


// UPLOAD
function uploadMedia(files, msg = "") {
    const placeholder = addUploadingPlaceholder();
    const bar = $("#" + placeholder).find(".upload-progress-fill");

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
        xhr: () => {
            let xhr = new XMLHttpRequest();
            xhr.upload.addEventListener("progress", e => {
                if (e.lengthComputable) bar.css("width", (e.loaded / e.total) * 100 + "%");
            });
            return xhr;
        },
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
