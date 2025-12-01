// ========================================
// SkyTruFiber Client Chat System
// chat_support.js — Full CSR Mirror + Gallery
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

    // Auto refresh
    loadInterval = setInterval(() => {
        if (!$("#preview-inline").is(":visible")) loadMessages(false);
    }, 1200);

    // Send events
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

    // Lightbox open click
    $(document).on("click", ".media-thumb, .media-video", function () {
        const group = $(this).closest(".message");
        const media = group.find(".media-thumb, .media-video");
        galleryItems = [];

        media.each(function () {
            galleryItems.push({
                src: $(this).attr("data-full"),
                type: $(this).is("img") ? "image" : "video"
            });
        });

        currentIndex = media.index(this);
        openLightbox(currentIndex);
    });

    // Lightbox controls
    $("#lightbox-next").click(() => {
        currentIndex = (currentIndex + 1) % galleryItems.length;
        openLightbox(currentIndex);
    });

    $("#lightbox-prev").click(() => {
        currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
        openLightbox(currentIndex);
    });

    $("#lightbox-close, #lightbox-overlay").click(e => {
        if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close") {
            $("#lightbox-overlay").fadeOut(200);
        }
    });

    // Scroll button
    const box = $("#chat-messages");
    const btn = $("#scroll-bottom-btn");

    box.on("scroll", () => {
        if (!box[0]) return;

        const atBottom =
            box[0].scrollHeight - box.scrollTop() - box.outerHeight() < 50;

        if (atBottom) btn.removeClass("show");
        else btn.addClass("show");
    });

    btn.click(() => {
        scrollToBottom();
        btn.removeClass("show");
    });

});

// Lightbox loader
function openLightbox(index) {
    const item = galleryItems[index];

    $("#lightbox-image").hide();
    $("#lightbox-video").hide();

    if (item.type === "image") {
        $("#lightbox-image").attr("src", item.src).show();
    } else {
        $("#lightbox-video").attr("src", item.src).show();
    }

    $("#lightbox-overlay").fadeIn(200);
}


// Scroll
function scrollToBottom() {
    const box = $("#chat-messages");
    if (!box.length || !box[0]) return;
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 300);
}


// Placeholder bubble
function addUploadingPlaceholder() {
    const id = "upload-" + Date.now();
    $("#chat-messages").append(`
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


// Load server messages
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


// Send message
function sendMessage() {
    const msg = $("#message-input").val().trim();
    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);
    if (!msg) return;

    $.post("send_message_client.php", { message: msg, username }, function () {
        $("#message-input").val("");
        loadMessages(true);
    }, "json");
}


// Preview
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


// Remove preview item
$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});


// Upload
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
