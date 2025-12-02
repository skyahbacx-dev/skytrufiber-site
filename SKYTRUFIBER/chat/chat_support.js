// ========================================
// SkyTruFiber Client Chat System
// chat_support.js — Instant DOM + Thumb Blob + Fast Upload + Gallery + Swipe
// + Emoji + Cancel Upload + Unsend + Bubble Reactions + Edit Message
// ========================================

let selectedFiles = [];
let lastMessageID = 0;
let loadInterval = null;
let galleryItems = [];
let currentIndex = 0;
let currentUploadXHR = null;
let reactingToMsgId = null;
const reactionChoices = ["👍", "❤️", "😂", "😮", "😢", "😡"];

const username = new URLSearchParams(window.location.search).get("username");

$(document).ready(function () {

    if (!username) {
        $("#chat-messages").html("<p style='padding:20px;text-align:center;color:#888;'>Invalid user.</p>");
        return;
    }

    // Intro animation
    $("#chat-messages").css({ opacity: 0, transform: "translateY(20px)" });
    setTimeout(() => {
        $("#chat-messages").css({
            opacity: 1,
            transform: "translateY(0)",
            transition: "all .45s ease"
        });
    }, 150);

    loadMessages(true);

    loadInterval = setInterval(() => {
        if (!$("#preview-inline").is(":visible")) loadMessages(false);
    }, 1200);

    // ---------------- SEND TEXT ----------------
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) { e.preventDefault(); sendMessage(); }
    });

    // ---------------- FILE UPLOAD ----------------
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // ---------------- LIGHTBOX ----------------
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

    // Swipe gestures
    let startX = 0;
    document.getElementById("lightbox-overlay").addEventListener("touchstart", e =>
        startX = e.changedTouches[0].clientX
    );
    document.getElementById("lightbox-overlay").addEventListener("touchend", e => {
        const endX = e.changedTouches[0].clientX;
        if (endX < startX - 50) nextLightbox();
        if (endX > startX + 50) prevLightbox();
    });

    // ---------------- SCROLL BUTTON ----------------
    const box = $("#chat-messages");
    const btn = $("#scroll-bottom-btn");

    box.on("scroll", () => {
        const atBottom = box[0].scrollHeight - box.scrollTop() - box.outerHeight() < 50;
        if (atBottom) btn.removeClass("show");
        else btn.addClass("show");
    });

    btn.click(() => { scrollToBottom(); btn.removeClass("show"); });

    // ---------------- LOGOUT ----------------
    $(document).on("click", "#logout-btn", () => window.location.href = "logout.php");

    // ---------------- DELETE / UNSEND ----------------
    $(document).on("click", ".delete-btn", function () {
        const msgID = $(this).data("id");
        $.post("delete_message_client.php", { id: msgID }, () => loadMessages(true));
    });

    // ---------------- EDIT MESSAGE ----------------
    $(document).on("click", ".edit-btn", function () {
        const msgID = $(this).data("id");
        const bubble = $(this).closest(".message").find(".message-bubble");
        const currentText = bubble.text();

        bubble.html(`
            <textarea class="edit-textarea">${currentText}</textarea>
            <div class="edit-actions">
                <button class="edit-save" data-id="${msgID}">Save</button>
                <button class="edit-cancel">Cancel</button>
            </div>
        `);
    });

    $(document).on("click", ".edit-save", function () {
        const msgID = $(this).data("id");
        const newText = $(this).closest(".message-bubble").find(".edit-textarea").val().trim();
        $.post("edit_message_client.php", { id: msgID, message: newText }, () => loadMessages(true));
    });

    $(document).on("click", ".edit-cancel", function () {
        loadMessages(false);
    });

    // ---------------- REACTIONS ----------------
    $(document).on("click", ".react-btn", function (e) {
        e.stopPropagation();
        reactingToMsgId = $(this).data("msg-id");

        const $picker = ensureReactionPicker();
        const btnOffset = $(this).offset();
        const pickerWidth = $picker.outerWidth();
        const pickerHeight = $picker.outerHeight();
        const btnWidth = $(this).outerWidth();

        $picker
            .css({
                top: btnOffset.top - pickerHeight - 12,
                left: btnOffset.left - (pickerWidth / 2) + (btnWidth / 2)
            })
            .addClass("show");
    });

    $(document).on("click", ".reaction-choice", function (e) {
        e.stopPropagation();
        const emoji = $(this).data("emoji");
        if (!reactingToMsgId) return;

        $.post("react_message_client.php", { chat_id: reactingToMsgId, emoji }, () => loadMessages(false));
        $("#reaction-picker").removeClass("show");
        reactingToMsgId = null;
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("#reaction-picker, .react-btn").length) {
            $("#reaction-picker").removeClass("show");
        }
    });

});

// ---------------- REACTION PICKER ----------------
function ensureReactionPicker() {
    let $picker = $("#reaction-picker");
    if ($picker.length) return $picker;

    $("body").append(`
        <div id="reaction-picker" class="reaction-picker">
            ${reactionChoices.map(e => `<button class='reaction-choice' data-emoji='${e}'>${e}</button>`).join("")}
        </div>
    `);
    return $("#reaction-picker");
}

// ---------------- INSTANT DOM ----------------
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

// ---------------- LOAD MESSAGES ----------------
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

// ---------------- SEND MESSAGE ----------------
function sendMessage() {
    const msg = $("#message-input").val().trim();
    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);
    if (!msg) return;

    appendClientMessageInstant(msg);

    $.post("send_message_client.php", { message: msg, username }, () => {
        $("#message-input").val("");
    }, "json");
}

// ---------------- PREVIEW FILES ----------------
function previewMultiple(files) {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(200);

    files.forEach((file, index) => {
        const thumb = file.type.startsWith("image")
            ? `<img src="${URL.createObjectURL(file)}" class="preview-thumb">`
            : `<div class="file-box">📎 ${file.name}</div>`;

        $("#preview-files").append(`
            <div class="preview-item">
                ${thumb}
                <button class="preview-remove" data-i="${index}">&times;</button>
            </div>
        `);
    });
}

$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});

// ---------------- UPLOAD ----------------
function uploadMedia(files, msg = "") {
    const placeholder = addUploadingPlaceholder();
    const bar = $("#" + placeholder).find(".upload-progress-fill");

    $("#" + placeholder).append(`
        <button class="cancel-upload-btn" data-target="${placeholder}">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `);

    const fd = new FormData();
    files.forEach(f => fd.append("media[]", f));
    fd.append("message", msg);
    fd.append("username", username);

    $("#preview-inline").slideUp(200);
    selectedFiles = [];
    $("#chat-upload-media").val("");
    $("#message-input").val("");

    currentUploadXHR = $.ajax({
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
        error: () => {
            $("#" + placeholder).remove();
        }
    });
}

$(document).on("click", ".cancel-upload-btn", function () {
    const id = $(this).data("target");
    if (currentUploadXHR) currentUploadXHR.abort();

    $("#" + id).fadeOut(200, function () {
        $(this).remove();
    });

    selectedFiles = [];
    $("#chat-upload-media").val("");
});

// ---------------- PLACEHOLDER BUBBLE ----------------
function addUploadingPlaceholder() {
    const id = "upload-" + Date.now();
    $("#chat-messages").append(`
        <div class="message sent" id="${id}">
            <div class="message-content">
                <div class="message-bubble">
                    <div class="upload-progress-bar">
                        <div class="upload-progress-fill"></div>
                    </div>
                </div>
                <div class="message-time">Uploading...</div>
            </div>
        </div>
    `);
    scrollToBottom();
    return id;
}

// ---------------- LIGHTBOX ----------------
function openLightbox(index) {
    const item = galleryItems[index];
    $("#lightbox-image, #lightbox-video").hide();
    $("#lightbox-overlay").fadeIn(200);

    if (item.type === "image") {
        $("#lightbox-image").attr("src", item.thumb).fadeIn(120);

        const full = new Image();
        full.onload = () =>
            $("#lightbox-image").fadeOut(80, () =>
                $("#lightbox-image").attr("src", item.full).fadeIn(160)
            );
        full.src = item.full;
        $("#lightbox-download").attr("href", item.full);

    } else {
        $("#lightbox-video").attr("src", item.full).fadeIn(180);
        $("#lightbox-download").attr("href", item.full);
    }
}

function nextLightbox() {
    currentIndex = (currentIndex + 1) % galleryItems.length;
    openLightbox(currentIndex);
}

function prevLightbox() {
    currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
    openLightbox(currentIndex);
}

// ---------------- SCROLL ----------------
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 250);
}
