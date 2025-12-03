// ========================================
// SkyTruFiber — Modernized Client Chat System
// Version: Ultra-Stable + Modern Viewer + Pinch Zoom
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

let lightboxScale = 1;
let lightboxTranslateX = 0;
let lightboxTranslateY = 0;
let isPanning = false;

const reactionChoices = ["👍", "❤️", "😂", "😮", "😢", "😡"];
const username = new URLSearchParams(window.location.search).get("username");

// ========================================
// INIT
// ========================================
$(document).ready(function () {

    if (!username) {
        $("#chat-messages").html("<p style='padding:20px;text-align:center;color:#888;'>Invalid user.</p>");
        return;
    }

    loadMessages(true);

    // POLLING (4 seconds)
    setInterval(() => {
        if (!editing && !activePopup && !$("#preview-inline").is(":visible")) {
            fetchNewMessages();
        }
    }, 4000);

    // SEND MESSAGE
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) { e.preventDefault(); sendMessage(); }
    });

    // UPLOAD MEDIA
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // CLOSE POPUPS
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length)
            closePopup();

        if (!$(e.target).closest("#reaction-picker, .react-btn").length)
            $("#reaction-picker").removeClass("show");
    });

    // OPEN MESSAGE MENU
    $(document).on("click", ".more-btn", function (e) {
        e.stopPropagation();
        const msgID = $(this).data("id");

        closePopup();

        $("body").append(buildPopup(msgID));
        activePopup = $("#msg-action-popup");

        const pos = $(this).offset();

        activePopup.css({
            top: pos.top - activePopup.outerHeight() - 8,
            left: pos.left - (activePopup.outerWidth() / 2) + 15
        }).fadeIn(120);
    });

    // POPUP OPTIONS
    $(document).on("click", ".popup-edit", function () {
        startEdit($(this).data("id"));
        closePopup();
    });

    $(document).on("click", ".popup-unsend", function () {
        $.post("delete_message_client.php", {
            id: $(this).data("id"),
            username
        }, () => loadMessages(false));
        closePopup();
    });

    $(document).on("click", ".popup-delete", function () {
        $.post("delete_message_client.php", {
            id: $(this).data("id"),
            username
        }, () => loadMessages(false));
        closePopup();
    });

    $(document).on("click", ".popup-cancel", closePopup);

    // REACTIONS
    $(document).on("click", ".react-btn", function (e) {
        e.stopPropagation();
        reactingToMsgId = $(this).data("msg-id");

        const picker = ensureReactionPicker();
        const rec = $(this).offset();

        picker.css({
            top: rec.top - picker.outerHeight() - 8,
            left: rec.left - (picker.outerWidth() / 2) + 15
        }).addClass("show");
    });

    $(document).on("click", ".reaction-choice", function () {
        $.post("react_message_client.php", {
            chat_id: reactingToMsgId,
            emoji: $(this).data("emoji")
        }, fetchNewMessages);
        $("#reaction-picker").removeClass("show");
    });

    // THEME TOGGLE
    $("#theme-toggle").on("click", toggleTheme);

});

// ========================================
// THEME ENGINE
// ========================================
function toggleTheme() {
    const root = document.documentElement;

    if (root.getAttribute("data-theme") === "dark") {
        root.setAttribute("data-theme", "light");
    } else {
        root.setAttribute("data-theme", "dark");
    }
}

// ========================================
// MESSAGE LOADING (FULL RENDER)
// ========================================
function loadMessages(scrollBottom = false) {
    $.post("load_messages_client.php", { username }, html => {

        $("#chat-messages").html(html);
        attachMediaEvents();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scrollBottom) scrollToBottom();
    });
}

// ========================================
// FETCH NEW MESSAGES (DOM DIFF)
// ========================================
function fetchNewMessages() {

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
        scrollToBottom();
    });
}

// ========================================
// SEND MESSAGE
// ========================================
function sendMessage() {
    const msg = $("#message-input").val().trim();

    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);
    if (!msg) return;

    appendClientMessageInstant(msg);

    $.post("send_message_client.php", { username, message: msg }, () => {
        $("#message-input").val("");
        fetchNewMessages();
    });
}

// Instant preview before upload
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

// ========================================
// EDIT MESSAGE
// ========================================
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
    const msgID = $(this).data("id");
    const newText = $(this).closest(".message-bubble").find(".edit-textarea").val().trim();

    $.post("edit_message_client.php", { id: msgID, message: newText }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", function () {
    editing = false;
    loadMessages(false);
});

// ========================================
// PREVIEW REMOVE
// ========================================
$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);

    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});

// ========================================
// POPUP MENU
// ========================================
function buildPopup(id) {
    return `
        <div id="msg-action-popup" class="msg-action-popup">
            <button class="popup-edit" data-id="${id}"><i class="fa-solid fa-pen"></i> Edit</button>
            <button class="popup-unsend" data-id="${id}"><i class="fa-solid fa-rotate-left"></i> Unsend</button>
            <button class="popup-delete" data-id="${id}"><i class="fa-solid fa-trash"></i> Delete</button>
            <button class="popup-cancel"><i class="fa-solid fa-xmark"></i> Cancel</button>
        </div>
    `;
}

function closePopup() {
    if (activePopup) {
        const p = activePopup;
        activePopup = null;
        p.fadeOut(120, function () {
            $(this).remove();
        });
    }
}

// ========================================
// REACTION PICKER
// ========================================
function ensureReactionPicker() {
    let picker = $("#reaction-picker");

    if (picker.length) return picker;

    $("body").append(`
        <div id="reaction-picker" class="reaction-picker">
            ${reactionChoices.map(e => `<button class="reaction-choice" data-emoji="${e}">${e}</button>`).join("")}
        </div>
    `);

    return $("#reaction-picker");
}

// ========================================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 200);
}
// ========================================
// SCROLL-TO-BOTTOM BUTTON LOGIC
// ========================================
const scrollBtn = document.getElementById("scroll-bottom-btn");

$("#chat-messages").on("scroll", function () {

    const box = this;

    const distanceFromBottom = box.scrollHeight - box.scrollTop - box.clientHeight;

    // Show if user is 70px or more above bottom
    if (distanceFromBottom > 70) {
        scrollBtn.classList.add("show");
    } else {
        scrollBtn.classList.remove("show");
    }
});

// Button click → scroll to bottom
scrollBtn.addEventListener("click", () => {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 250);
});

// ========================================
// MEDIA PREVIEW (before upload)
// ========================================
function previewMultiple(files) {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(150);

    files.forEach((file, i) => {
        const isImage = file.type.startsWith("image");
        const url = URL.createObjectURL(file);

        $("#preview-files").append(`
            <div class="preview-item">
                ${
                    isImage
                        ? `<img src="${url}" class="preview-thumb">`
                        : `<div class="file-box">📎 ${file.name}</div>`
                }
                <button class="preview-remove" data-i="${i}">&times;</button>
            </div>
        `);
    });
}

// ========================================
// UPLOAD MEDIA
// ========================================
function uploadMedia(files, msg) {
    const form = new FormData();
    form.append("username", username);
    form.append("message", msg);

    files.forEach((file, i) => form.append("media[]", file));

    currentUploadXHR = $.ajax({
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
// MEDIA EVENTS (image click → open viewer)
// ========================================
function attachMediaEvents() {

    // ------------------------------------
    // IMAGE CLICK → OPEN LIGHTBOX
    // ------------------------------------
    $(".media-thumb").off("click").on("click", function () {

        const full = $(this).data("full");
        const container = $(this).closest(".carousel-container");

        galleryItems = [];
        currentIndex = 0;

        // Build gallery
        const items = container.find(".media-thumb");
        items.each(function (i) {
            galleryItems.push($(this).data("full"));
            if ($(this).data("full") === full) currentIndex = i;
        });

        showLightboxImage(full);

    });

    // ------------------------------------
    // VIDEO CLICK
    // ------------------------------------
    $(".media-video").off("click").on("click", function () {
        const full = $(this).data("full");

        resetLightboxTransform();
        $("#lightbox-image").hide();
        $("#lightbox-video").attr("src", full).show();

        $("#lightbox-overlay").addClass("show");
        updateLightboxIndex();
    });

    // ------------------------------------
    // LIGHTBOX NAVIGATION
    // ------------------------------------
    $("#lightbox-prev").off("click").on("click", function () {
        navigateGallery(-1);
    });

    $("#lightbox-next").off("click").on("click", function () {
        navigateGallery(1);
    });

    // ------------------------------------
    // CLOSE LIGHTBOX (X BUTTON)
    // ------------------------------------
    $("#lightbox-close").off("click").on("click", function () {
        closeLightbox();
    });

    // ------------------------------------
    // BACKGROUND TAP CLOSE (smart)
    // ------------------------------------
    $("#lightbox-overlay").off("click").on("click", function (e) {

        const isImage = $("#lightbox-image").is(":visible");

        // If clicking directly ON image, prevent closing
        if (isImage) {
            const img = document.getElementById("lightbox-image");
            const rect = img.getBoundingClientRect();
            const inside =
                e.clientX >= rect.left &&
                e.clientX <= rect.right &&
                e.clientY >= rect.top &&
                e.clientY <= rect.bottom;

            if (inside) return; // do NOT close if image tapped
        }

        // Do not close if zoomed in
        if (lightboxScale > 1.02) return;

        closeLightbox();
    });
}

// ========================================
// SHOW IMAGE IN LIGHTBOX
// ========================================
function showLightboxImage(src) {
    resetLightboxTransform();

    $("#lightbox-video").hide();
    $("#lightbox-image").attr("src", src).show();

    $("#lightbox-overlay").addClass("show");
    updateLightboxIndex();
}

// ========================================
// GALLERY NAVIGATION (Prev / Next)
// ========================================
function navigateGallery(step) {
    if (galleryItems.length <= 1) return;

    currentIndex = (currentIndex + step + galleryItems.length) % galleryItems.length;

    resetLightboxTransform();
    $("#lightbox-image").attr("src", galleryItems[currentIndex]);

    updateLightboxIndex();
}

// Update "1/4" indicator
function updateLightboxIndex() {
    if (galleryItems.length > 1) {
        $("#lightbox-index").text(`${currentIndex + 1} / ${galleryItems.length}`).show();
    } else {
        $("#lightbox-index").hide();
    }

    const currentSrc = galleryItems[currentIndex];
    $("#lightbox-download").attr("href", currentSrc);
}

// ========================================
// CLOSE LIGHTBOX
// ========================================
function closeLightbox() {
    $("#lightbox-overlay").removeClass("show");
    $("#lightbox-image").hide();
    $("#lightbox-video").hide();
    resetLightboxTransform();
}

// ========================================
// LIGHTBOX TRANSFORM RESET
// ========================================
function resetLightboxTransform() {
    lightboxScale = 1;
    lightboxTranslateX = 0;
    lightboxTranslateY = 0;

    $("#lightbox-image").css({
        transform: "translate(0px, 0px) scale(1)"
    });
}
// ========================================
// PART 3 — FULL GESTURE SYSTEM
// ========================================

// DOM ELEMENT
const imgEl = document.getElementById("lightbox-image");

// TOUCH STATE
let touchStartDistance = 0;
let lastTouchX = 0;
let lastTouchY = 0;

// SWIPE STATE
let swipeStartX = 0;
let swipeEndX = 0;

// Flag to prevent background scroll
document.addEventListener("touchmove", function (e) {
    if ($("#lightbox-overlay").hasClass("show")) {
        e.preventDefault(); 
    }
}, { passive: false });

// ========================================
// HELPER — DISTANCE BETWEEN 2 TOUCHES
// ========================================
function getTouchDistance(ev) {
    const dx = ev.touches[0].clientX - ev.touches[1].clientX;
    const dy = ev.touches[0].clientY - ev.touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

// ========================================
// APPLY TRANSFORM
// ========================================
function updateTransform() {
    imgEl.style.transform =
        `translate(${lightboxTranslateX}px, ${lightboxTranslateY}px) scale(${lightboxScale})`;
}

// ========================================
// TOUCH START
// ========================================
imgEl.addEventListener("touchstart", function (ev) {

    if (ev.touches.length === 1) {
        // Single finger → pan
        isPanning = true;
        lastTouchX = ev.touches[0].clientX;
        lastTouchY = ev.touches[0].clientY;

        // Swipe beginning
        swipeStartX = lastTouchX;

    } else if (ev.touches.length === 2) {
        // Two fingers → pinch
        isPanning = false;
        touchStartDistance = getTouchDistance(ev);
    }

}, { passive: false });

// ========================================
// TOUCH MOVE
// ========================================
imgEl.addEventListener("touchmove", function (ev) {

    if (ev.touches.length === 1 && isPanning && lightboxScale > 1.02) {
        // Drag / Pan
        let x = ev.touches[0].clientX;
        let y = ev.touches[0].clientY;

        lightboxTranslateX += (x - lastTouchX);
        lightboxTranslateY += (y - lastTouchY);

        lastTouchX = x;
        lastTouchY = y;

        updateTransform();
    }

    if (ev.touches.length === 2) {
        // Pinch zoom
        let newDist = getTouchDistance(ev);
        let ratio = newDist / touchStartDistance;

        let newScale = lightboxScale * ratio;

        // Scale boundaries
        if (newScale < 1) newScale = 1;
        if (newScale > 4) newScale = 4;

        lightboxScale = newScale;
        touchStartDistance = newDist;

        updateTransform();
    }

}, { passive: false });

// ========================================
// TOUCH END
// ========================================
imgEl.addEventListener("touchend", function (ev) {

    // SWIPE DETECTION (only when scale == 1)
    if (ev.touches.length === 0 && !isPanning && lightboxScale <= 1.02) {

        swipeEndX = lastTouchX;
        const diff = swipeEndX - swipeStartX;

        if (Math.abs(diff) > 60) {
            // Swipe left → next
            if (diff < 0) navigateGallery(1);

            // Swipe right → previous
            if (diff > 0) navigateGallery(-1);
        }
    }

    // Reset if fingers removed
    if (lightboxScale <= 1.02) {
        lightboxScale = 1;
        lightboxTranslateX = 0;
        lightboxTranslateY = 0;
        updateTransform();
    }

    isPanning = false;
}, { passive: false });

// ========================================
// DOUBLE TAP ZOOM
// ========================================
let lastTap = 0;

imgEl.addEventListener("touchend", function (ev) {
    let now = Date.now();
    let tapTime = now - lastTap;

    if (tapTime < 250) {
        // Double-tap center zoom
        if (lightboxScale > 1.05) {
            // Reset zoom
            lightboxScale = 1;
            lightboxTranslateX = 0;
            lightboxTranslateY = 0;
        } else {
            // Zoom in
            lightboxScale = 2;
        }
        updateTransform();
    }

    lastTap = now;
});

// ========================================
// DESKTOP: WHEEL ZOOM
// ========================================
imgEl.addEventListener("wheel", function (ev) {
    ev.preventDefault();

    let delta = ev.deltaY;
    let zoomAmount = -delta * 0.001;

    lightboxScale += zoomAmount;

    if (lightboxScale < 1) lightboxScale = 1;
    if (lightboxScale > 4) lightboxScale = 4;

    updateTransform();

}, { passive: false });

// ========================================
// DESKTOP: DRAG (mouse)
// ========================================
let mouseDown = false;
let lastMouseX = 0;
let lastMouseY = 0;

imgEl.addEventListener("mousedown", function (ev) {
    if (lightboxScale <= 1.02) return;

    mouseDown = true;
    lastMouseX = ev.clientX;
    lastMouseY = ev.clientY;
});

document.addEventListener("mousemove", function (ev) {
    if (!mouseDown || lightboxScale <= 1.02) return;

    lightboxTranslateX += (ev.clientX - lastMouseX);
    lightboxTranslateY += (ev.clientY - lastMouseY);

    lastMouseX = ev.clientX;
    lastMouseY = ev.clientY;

    updateTransform();
});

document.addEventListener("mouseup", function () {
    mouseDown = false;
});


// ========================================
// END OF PART 3 — COMPLETE
// ========================================
