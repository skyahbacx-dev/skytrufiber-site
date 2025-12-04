// ========================================
// SkyTruFiber Chat System — PART 1 / 4
// Init + Theme + Sending + Preview + Upload
// ========================================

// GLOBAL STATE
let selectedFiles = [];
let lastMessageID = 0;
let currentUploadXHR = null;
let editing = false;
let activePopup = null;
let reactingToMsgId = null;

let galleryItems = [];
let currentIndex = 0;

const reactionChoices = ["👍","❤️","😂","😮","😢","😡"];
const username = new URLSearchParams(window.location.search).get("username");

// ========================================
// INIT
// ========================================
$(document).ready(function () {

    if (!username) {
        $("#chat-messages").html(
            "<p style='padding:20px;text-align:center;color:#888;'>Invalid user.</p>"
        );
        return;
    }

    loadMessages(true);

    // Polling
    setInterval(() => {
        if (!editing && !activePopup && !$("#preview-inline").is(":visible")) {
            fetchNewMessages();
        }
    }, 4000);

    // Send message
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) { e.preventDefault(); sendMessage(); }
    });

    // Upload media
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // Close popups
    $(document).on("click", function (e) {

        if (!$(e.target).closest("#msg-action-popup, .more-btn").length)
            closePopup();

        if (!$(e.target).closest("#reaction-picker, .react-btn").length)
            $("#reaction-picker").removeClass("show");
    });

    // Theme toggle
    $("#theme-toggle").on("click", toggleTheme);
});

// ========================================
// THEME
// ========================================
function toggleTheme() {
    const root = document.documentElement;
    root.setAttribute("data-theme",
        root.getAttribute("data-theme") === "dark" ? "light" : "dark"
    );
}

// ========================================
// SEND MESSAGE
// ========================================
function sendMessage() {
    const msg = $("#message-input").val().trim();

    if (selectedFiles.length > 0)
        return uploadMedia(selectedFiles, msg);

    if (!msg) return;

    appendClientMessageInstant(msg);

    $.post("send_message_client.php", { username, message: msg }, () => {
        $("#message-input").val("");
        fetchNewMessages();
    });
}

// Quick preview bubble
function appendClientMessageInstant(msg) {
    $("#chat-messages").append(`
        <div class="message sent fadeup">
            <div class="message-avatar">
                <img src="/upload/default-avatar.png">
            </div>
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
            </div>
        </div>
    `);
    scrollToBottom();
}

// PREVIEW REMOVE
$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);

    if (selectedFiles.length)
        previewMultiple(selectedFiles);
    else
        $("#preview-inline").slideUp(200);
});

// PREVIEW MULTIPLE
function previewMultiple(files) {

    $("#preview-files").html("");
    $("#preview-inline").slideDown(150);

    files.forEach((file, i) => {
        const url = URL.createObjectURL(file);
        $("#preview-files").append(`
            <div class="preview-item">
                <img src="${url}" class="preview-thumb">
                <button class="preview-remove" data-i="${i}">&times;</button>
            </div>
        `);
    });
}

// UPLOAD MEDIA
function uploadMedia(files, msg) {
    const form = new FormData();
    form.append("username", username);
    form.append("message", msg);

    files.forEach(f => form.append("media[]", f));

    $.ajax({
        url: "upload_media_client.php",
        method: "POST",
        data: form,
        contentType: false,
        processData: false,
        success: () => {
            selectedFiles = [];
            $("#preview-inline").slideUp(200);
            fetchNewMessages();
        }
    });
}
// ========================================
// SkyTruFiber Chat System — PART 2 / 4
// Load Messages + Scroll Button + Edit + Popup Menu
// ========================================

// FULL LOAD
function loadMessages(scrollBottom = false) {

    $.post("load_messages_client.php", { username }, html => {

        $("#chat-messages").html(html);

        attachMediaEvents();
        bindReactionButtons();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scrollBottom) scrollToBottom();
    });
}

// NEW MESSAGES
function fetchNewMessages() {

    if (editing || activePopup || $("#preview-inline").is(":visible")) return;

    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);
        const newMsgs = temp.find(".message");

        const container = $("#chat-messages");
        const currentLast = container.find(".message:last").data("msg-id") || 0;

        newMsgs.each(function () {
            const id = $(this).data("msg-id");
            if (id > currentLast) container.append($(this));
        });

        attachMediaEvents();
        bindReactionButtons();

        const box = container[0];
        const distance = box.scrollHeight - box.scrollTop - box.clientHeight;

        if (distance < 120) scrollToBottom();
    });
}

// SCROLL BUTTON
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 230);
}

$("#chat-messages").on("scroll", function () {
    const box = this;
    const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

    if (dist > 70) $("#scroll-bottom-btn").addClass("show");
    else $("#scroll-bottom-btn").removeClass("show");
});

document.getElementById("scroll-bottom-btn")
    .addEventListener("click", () => scrollToBottom());

// EDIT
function startEdit(msgID) {
    editing = true;
    const bubble = $(`.message[data-msg-id='${msgID}'] .message-bubble`);
    const original = bubble.text();

    bubble.html(`
        <textarea class="edit-textarea">${original}</textarea>
        <div class="edit-actions">
            <button class="edit-save" data-id="${msgID}">Save</button>
            <button class="edit-cancel">Cancel</button>
        </div>
    `);
}

$(document).on("click", ".edit-save", function () {
    const id = $(this).data("id");
    const newText = $(this).closest(".message-bubble")
                     .find(".edit-textarea").val().trim();

    $.post("edit_message_client.php", { id, message: newText }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", function () {
    editing = false;
    loadMessages(false);
});

// POPUP MENU
function buildPopup(id) {
    return `
    <div id="msg-action-popup" class="msg-action-popup">

        <button class="popup-edit" data-id="${id}">
            <i class="fa-solid fa-pen"></i> Edit
        </button>

        <button class="popup-unsend" data-id="${id}">
            <i class="fa-solid fa-rotate-left"></i> Unsend
        </button>

        <button class="popup-delete" data-id="${id}">
            <i class="fa-solid fa-trash"></i> Delete
        </button>

        <button class="popup-cancel">
            <i class="fa-solid fa-xmark"></i> Cancel
        </button>

    </div>`;
}

$(document).on("click", ".more-btn", function (e) {
    e.stopPropagation();

    closePopup();
    const id = $(this).data("id");

    $("body").append(buildPopup(id));
    activePopup = $("#msg-action-popup");

    const pos = $(this).offset();

    activePopup.css({
        top: pos.top - activePopup.outerHeight() - 5,
        left: pos.left - (activePopup.outerWidth() / 2) + 15,
        zIndex: 999999
    }).fadeIn(120);
});

function closePopup() {
    if (activePopup) {
        activePopup.fadeOut(120, () => activePopup.remove());
        activePopup = null;
    }
}

$(document).on("click", ".popup-unsend", function () {
    $.post("delete_message_client.php", {
        id: $(this).data("id"), username
    }, () => loadMessages(false));
    closePopup();
});

$(document).on("click", ".popup-delete", function () {
    $.post("delete_message_client.php", {
        id: $(this).data("id"), username
    }, () => loadMessages(false));
    closePopup();
});

$(document).on("click", ".popup-cancel", closePopup);
// ========================================
// SkyTruFiber Chat System — PART 3 / 4
// Messenger-style Reactions
// ========================================

// REACTION PICKER
function ensureReactionPicker() {
    let picker = $("#reaction-picker");

    if (picker.length) return picker;

    $("body").append(`
        <div id="reaction-picker" class="reaction-picker">
            ${reactionChoices.map(e =>
                `<button class="reaction-choice" data-emoji="${e}">${e}</button>`
            ).join("")}
        </div>
    `);

    return $("#reaction-picker");
}

// BIND REACTION BUTTONS
function bindReactionButtons() {
    $(".react-btn").off("click").on("click", function (e) {
        e.stopPropagation();

        reactingToMsgId = $(this).data("msg-id");

        const picker = ensureReactionPicker();
        const pos = $(this).offset();

        picker.css({
            top: pos.top - picker.outerHeight() - 10,
            left: pos.left - picker.outerWidth() / 2 + 15,
            zIndex: 999999
        }).addClass("show");
    });
}

// SELECT REACTION
$(document).on("click", ".reaction-choice", function () {

    $.post("react_message_client.php", {
        chat_id: reactingToMsgId,
        emoji: $(this).data("emoji")
    }, () => updateReactionBar(reactingToMsgId));

    $("#reaction-picker").removeClass("show");
});

// UPDATE REACTION BAR ONLY
function updateReactionBar(msgID) {

    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);
        const newBar = temp
            .find(`.message[data-msg-id='${msgID}']`)
            .find(".reaction-bar");

        const oldBar = $(`.message[data-msg-id='${msgID}']`)
            .find(".reaction-bar");

        if (oldBar.length)
            oldBar.replaceWith(newBar.clone());
        else
            $(`.message[data-msg-id='${msgID}'] .message-content`)
                .append(newBar.clone());
    });
}

// CLOSE REACTION PICKER
$(document).on("click", function (e) {
    if (!$(e.target).closest("#reaction-picker, .react-btn").length)
        $("#reaction-picker").removeClass("show");
});
// ========================================
// SkyTruFiber Chat System — PART 4 / 4
// Lightbox + Gestures + Gallery
// ========================================

let lightboxScale = 1;
let lightboxTranslateX = 0;
let lightboxTranslateY = 0;
let isPanning = false;

const imgEl = document.getElementById("lightbox-image");

// ATTACH MEDIA EVENTS
function attachMediaEvents() {

    // IMAGE CLICK
    $(".media-grid img, .media-thumb").off("click").on("click", function () {

        const full = $(this).data("full");
        if (!full) return;

        const grid = $(this).closest(".media-grid");
        const items = grid.find("img");

        galleryItems = [];
        currentIndex = 0;

        items.each(function (i) {
            galleryItems.push($(this).data("full"));
            if ($(this).data("full") === full) currentIndex = i;
        });

        showLightboxImage(full);
    });

    // VIDEO CLICK
    $(".media-grid video, .media-video").off("click").on("click", function () {
        const full = $(this).data("full");
        resetLightboxTransform();
        $("#lightbox-image").hide();
        $("#lightbox-video").attr("src", full).show();
        $("#lightbox-overlay").addClass("show");
        updateLightboxIndex();
    });

    // CONTROLS
    $("#lightbox-prev").off("click").on("click", () => navigateGallery(-1));
    $("#lightbox-next").off("click").on("click", () => navigateGallery(1));
    $("#lightbox-close").off("click").on("click", closeLightbox);

    $("#lightbox-overlay").off("click").on("click", function (e) {

        if ($("#lightbox-image").is(":visible")) {
            const img = imgEl.getBoundingClientRect();
            const inside = (
                e.clientX >= img.left &&
                e.clientX <= img.right &&
                e.clientY >= img.top &&
                e.clientY <= img.bottom
            );
            if (inside) return;
        }

        if (lightboxScale > 1.02) return;

        closeLightbox();
    });
}

// SHOW IMAGE
function showLightboxImage(src) {
    resetLightboxTransform();
    $("#lightbox-video").hide();
    $("#lightbox-image").attr("src", src).show();
    $("#lightbox-overlay").addClass("show");
    updateLightboxIndex();
}

// GALLERY NAVIGATE
function navigateGallery(step) {
    if (!galleryItems.length) return;
    currentIndex = (currentIndex + step + galleryItems.length) % galleryItems.length;
    resetLightboxTransform();
    $("#lightbox-image").attr("src", galleryItems[currentIndex]);
    updateLightboxIndex();
}

// INDEX + DOWNLOAD
function updateLightboxIndex() {
    if (galleryItems.length > 1)
        $("#lightbox-index").text(`${currentIndex + 1} / ${galleryItems.length}`).show();
    else $("#lightbox-index").hide();

    $("#lightbox-download").attr("href", galleryItems[currentIndex]);
}

// CLOSE
function closeLightbox() {
    $("#lightbox-overlay").removeClass("show");
    $("#lightbox-image, #lightbox-video").hide();
    resetLightboxTransform();
}

// RESET
function resetLightboxTransform() {
    lightboxScale = 1;
    lightboxTranslateX = 0;
    lightboxTranslateY = 0;

    imgEl.style.transform = "translate(0px,0px) scale(1)";
}

// TOUCH + MOUSE GESTURES
let touchStartDistance = 0;
let lastTouchX = 0;
let lastTouchY = 0;
let swipeStartX = 0;

// BLOCK SCROLL
document.addEventListener("touchmove", e => {
    if ($("#lightbox-overlay").hasClass("show")) e.preventDefault();
}, { passive:false });

// DISTANCE FUNCTION
function getTouchDistance(ev) {
    const dx = ev.touches[0].clientX - ev.touches[1].clientX;
    const dy = ev.touches[0].clientY - ev.touches[1].clientY;
    return Math.sqrt(dx*dx + dy*dy);
}

// TOUCH START
imgEl.addEventListener("touchstart", ev => {

    if (ev.touches.length === 1) {
        isPanning = true;
        lastTouchX = ev.touches[0].clientX;
        lastTouchY = ev.touches[0].clientY;
        swipeStartX = lastTouchX;
    } else if (ev.touches.length === 2) {
        isPanning = false;
        touchStartDistance = getTouchDistance(ev);
    }

}, { passive:false });

// TOUCH MOVE
imgEl.addEventListener("touchmove", ev => {

    if (ev.touches.length === 1 && isPanning && lightboxScale > 1.02) {

        let x = ev.touches[0].clientX;
        let y = ev.touches[0].clientY;

        lightboxTranslateX += (x - lastTouchX);
        lightboxTranslateY += (y - lastTouchY);

        lastTouchX = x;
        lastTouchY = y;

        updateTransform();
    }

    if (ev.touches.length === 2) {

        let newDist = getTouchDistance(ev);
        let ratio = newDist / touchStartDistance;

        let newScale = lightboxScale * ratio;
        newScale = Math.max(1, Math.min(4, newScale));

        lightboxScale = newScale;
        touchStartDistance = newDist;

        updateTransform();
    }

}, { passive:false });

// TOUCH END (swipe)
imgEl.addEventListener("touchend", ev => {

    if (ev.touches.length === 0 && lightboxScale <= 1.02) {

        const diff = lastTouchX - swipeStartX;

        if (Math.abs(diff) > 60) {
            if (diff > 0) navigateGallery(1);
            if (diff < 0) navigateGallery(-1);
        }
    }

    if (lightboxScale <= 1.02) resetLightboxTransform();

    isPanning = false;

}, { passive:false });

// DOUBLE TAP
let lastTap = 0;
imgEl.addEventListener("touchend", () => {
    const now = Date.now();

    if (now - lastTap < 250) {
        if (lightboxScale > 1.05) resetLightboxTransform();
        else {
            lightboxScale = 2;
            updateTransform();
        }
    }
    lastTap = now;
});

// MOUSE ZOOM
imgEl.addEventListener("wheel", ev => {
    ev.preventDefault();
    lightboxScale += -ev.deltaY * 0.001;
    lightboxScale = Math.max(1, Math.min(4, lightboxScale));
    updateTransform();
}, { passive:false });

// DRAG (mouse)
let mouseDown = false;
let lastMouseX = 0;
let lastMouseY = 0;

imgEl.addEventListener("mousedown", ev => {
    if (lightboxScale <= 1.02) return;
    mouseDown = true;
    lastMouseX = ev.clientX;
    lastMouseY = ev.clientY;
});
document.addEventListener("mousemove", ev => {
    if (!mouseDown || lightboxScale <= 1.02) return;

    lightboxTranslateX += (ev.clientX - lastMouseX);
    lightboxTranslateY += (ev.clientY - lastMouseY);

    lastMouseX = ev.clientX;
    lastMouseY = ev.clientY;

    updateTransform();
});
document.addEventListener("mouseup", () => {
    mouseDown = false;
});

// UPDATE TRANSFORM
function updateTransform() {
    imgEl.style.transform =
        `translate(${lightboxTranslateX}px,${lightboxTranslateY}px) scale(${lightboxScale})`;
}
