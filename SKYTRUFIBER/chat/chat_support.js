// ========================================
// SkyTruFiber Client Chat System
// chat_support.js — Full CSR Mirror + Gallery + Fast Upload + Swipe
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

    loadMessages(true);

    loadInterval = setInterval(() => {
        if (!$("#preview-inline").is(":visible")) loadMessages(false);
    }, 1200);

    // Input & Send
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Upload
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // ========= LIGHTBOX OPEN =========
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

    $("#lightbox-next").click(() => nextLightbox());
    $("#lightbox-prev").click(() => prevLightbox());

    $("#lightbox-close, #lightbox-overlay").click(e => {
        if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close") {
            $("#lightbox-overlay").fadeOut(200);
        }
    });

    // TOUCH SWIPE
    let startX = 0;
    document.getElementById("lightbox-overlay").addEventListener("touchstart", e => {
        startX = e.changedTouches[0].clientX;
    });

    document.getElementById("lightbox-overlay").addEventListener("touchend", e => {
        let endX = e.changedTouches[0].clientX;
        if (endX < startX - 50) nextLightbox();
        if (endX > startX + 50) prevLightbox();
    });

    // SCROLL BUTTON
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

});


// ========= LIGHTBOX HANDLER =========
function openLightbox(index) {
    const item = galleryItems[index];

    $("#lightbox-image").hide();
    $("#lightbox-video").hide();
    $("#lightbox-overlay").fadeIn(200);

    if (item.type === "image") {
        $("#lightbox-image").attr("src", item.thumb).fadeIn(120);

        const fullImg = new Image();
        fullImg.onload = () => {
            $("#lightbox-image").fadeOut(80, () => {
                $("#lightbox-image").attr("src", item.full).fadeIn(180);
            });
        };
        fullImg.src = item.full;

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


// ========= SCROLL =========
function scrollToBottom() {
    const box = $("#chat-messages");
    if (!box.length) return;
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 300);
}


// ========= UPLOADING PLACEHOLDER =========
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


// ========= LOAD MESSAGES =========
function loadMessages(scrollBottom = false) {
    $.post("load_messages_client.php", { username }, function (html) {
        const incoming = $(html);
        if (!incoming.length) return;

        const newID = parseInt(incoming.last().attr("data-msg-id")) || 0;
        if (newID > lastMessageID) {
            lastMessageID = newID;
            $("#chat-messages").append(incoming);
            if (scrollBottom) scrollToBottom();
        }
    });
}


// ========= SEND MESSAGE =========
function sendMessage() {
    const msg = $("#message-input").val().trim();
    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);
    if (!msg) return;

    $.post("send_message_client.php", { message: msg, username }, function () {
        $("#message-input").val("");
        loadMessages(true);
    }, "json");
}


// ========= PREVIEW UPLOAD THUMBNAILS =========
function previewMultiple(files) {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(200);

    files.forEach((file, index) => {
        const removeBtn = `<button class="preview-remove" data-i="${index}">&times;</button>`;
        const preview = file.type.startsWith("image")
            ? `<img src="${URL.createObjectURL(file)}" class="preview-thumb">`
            : `<div class="file-box">📎 ${file.name}</div>`;

        $("#preview-files").append(`
            <div class="preview-item">
                ${preview}
                ${removeBtn}
            </div>
        `);
    });
}


// remove single preview & full reset
$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});

$("#preview-close").on("click", () => {
    selectedFiles = [];
    $("#preview-files").html("");
    $("#preview-inline").slideUp(200);
});


// ========= UPLOAD MEDIA =========
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
        xhr: function () {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", e => {
                if (e.lengthComputable) {
                    bar.css("width", (e.loaded / e.total) * 100 + "%");
                }
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
