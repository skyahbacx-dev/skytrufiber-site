
// ========================================
// SkyTruFiber Chat System — PART 1 / 4
// Init + Sending + Preview + Theme
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

    // Load initial messages
    loadMessages(true);

    // Poll for new messages (only when not editing)
    setInterval(() => {
        if (!editing && !activePopup && !$("#preview-inline").is(":visible")) {
            fetchNewMessages();
        }
    }, 4000);

    // Send message
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });
function bindReactionButtons() {
    $(".react-btn").off("click").on("click", function (e) {
        e.stopPropagation();

        reactingToMsgId = $(this).data("msg-id");

        const picker = ensureReactionPicker();
        const pos = $(this).offset();

        picker.css({
            top: pos.top - picker.outerHeight() - 10,
            left: pos.left - (picker.outerWidth() / 2) + 15
        }).addClass("show");
    });
}
function showReactionPicker(btn) {
    const picker = ensureReactionPicker();
    const rect = btn.getBoundingClientRect();

    picker.css({
        top: rect.top - picker.outerHeight() - 12 + window.scrollY,
        left: rect.left + rect.width / 2 - picker.outerWidth() / 2 + window.scrollX,
        zIndex: 30000
    }).addClass("show");
}

$(document).on("click", ".react-btn", function (e) {
    e.stopPropagation();
    reactingToMsgId = $(this).data("msg-id");
    showReactionPicker(this);
});

// ========================================
// UPLOAD MEDIA BUTTON
// ========================================
        $("#upload-btn").click(() => {
        $("#chat-upload-media").click();
    });

        $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // Close popups
    $(document).on("click", function (e) {

        // Close action popup
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length)
            closePopup();

        // Close reaction picker
        if (!$(e.target).closest("#reaction-picker, .react-btn").length)
            $("#reaction-picker").removeClass("show");
    });

    // Theme toggle
    $("#theme-toggle").on("click", toggleTheme);
});

// ========================================
// THEME TOGGLE
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

    // If sending media
    if (selectedFiles.length > 0)
        return uploadMedia(selectedFiles, msg);

    if (!msg) return;

    appendClientMessageInstant(msg);

    $.post("send_message_client.php", { username, message: msg }, () => {
        $("#message-input").val("");
        fetchNewMessages();
    });
}

// Preview self message instantly
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
// REMOVE ITEM FROM PREVIEW LIST
// ========================================
$(document).on("click", ".preview-remove", function () {
    const index = $(this).data("i");
    selectedFiles.splice(index, 1);

    if (selectedFiles.length) {
        previewMultiple(selectedFiles);
    } else {
        $("#preview-inline").slideUp(200);
    }
});


// ========================================
// PREVIEW BAR (before upload) — FIXED SIZE
// ========================================
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


// ========================================
// UPLOAD MEDIA TO SERVER
// ========================================
function uploadMedia(files, msg) {

    const form = new FormData();
    form.append("username", username);
    form.append("message", msg);

    files.forEach(f => form.append("media[]", f));

    currentUploadXHR = $.ajax({
        url: "upload_media_client.php",
        method: "POST",
        data: form,
        processData: false,
        contentType: false,

        success: () => {
            selectedFiles = [];
            $("#preview-inline").slideUp(200);
            fetchNewMessages();
        }
    });
}
// ========================================
// SkyTruFiber Chat System — PART 2 / 4
// Messages + Editing + Popup Menu + Scroll Button
// ========================================

// ========================================
// FULL MESSAGE LOAD (initial render)
// ========================================
function loadMessages(scrollBottom = false) {
    $.post("load_messages_client.php", { username }, html => {

        $("#chat-messages").html(html);

        attachMediaEvents();
        bindReactionButtons();   // <— FIXED

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scrollBottom) scrollToBottom();
    });
}


// ========================================
// FETCH NEW MESSAGES (DOM DIFF) — FIXED AUTO-SCROLL
// ========================================
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
        bindReactionButtons();   // <— FIXED

        const box = container[0];
        const distanceFromBottom = box.scrollHeight - box.scrollTop - box.clientHeight;

        if (distanceFromBottom < 120) scrollToBottom();
    });
}



// ========================================
// SCROLL TO BOTTOM
// ========================================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 220);
}

// ========================================
// SCROLL-TO-BOTTOM BUTTON
// ========================================
const scrollBtn = document.getElementById("scroll-bottom-btn");

$("#chat-messages").on("scroll", function () {

    const box = this;
    const distanceFromBottom = box.scrollHeight - box.scrollTop - box.clientHeight;

    if (distanceFromBottom > 70) {
        scrollBtn.classList.add("show");
    } else {
        scrollBtn.classList.remove("show");
    }
});

scrollBtn.addEventListener("click", () => scrollToBottom());

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
// POPUP MENU (3-dot actions)
// ========================================
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

        </div>
    `;
}

// OPEN POPUP
$(document).on("click", ".more-btn", function (e) {
    e.stopPropagation();
    closePopup();

    const msgID = $(this).data("id");
    $("body").append(buildPopup(msgID));

    activePopup = $("#msg-action-popup");

    const pos = $(this).offset();
    const popupHeight = activePopup.outerHeight();

    activePopup.css({
        top: pos.top - popupHeight - 5,
        left: pos.left - (activePopup.outerWidth() / 2) + 15,
        zIndex: 999999 // FIX: prevents overlapping inside lightbox
    }).fadeIn(120);
});

// CLOSE POPUP
function closePopup() {
    if (activePopup) {
        const p = activePopup;
        activePopup = null;
        p.fadeOut(120, () => p.remove());
    }
}

// POPUP → UNSEND / DELETE
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
// ========================================
// SkyTruFiber Chat System — PART 3 / 4
// Messenger-Style Reactions System
// ========================================

// ========================================
// REACTION PICKER CREATOR
// ========================================
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

// ========================================
// OPEN REACTION PICKER
// ========================================
$(document).on("click", ".react-btn", function (e) {
    e.stopPropagation();

    reactingToMsgId = $(this).data("msg-id");

    const picker = ensureReactionPicker();
    const pos = $(this).offset();

    picker.css({
        top: pos.top - picker.outerHeight() - 10,
        left: pos.left - (picker.outerWidth() / 2) + 15,
        zIndex: 999999 // FIX lightbox layering
    }).addClass("show");
});

// ========================================
// SELECT REACTION → SEND TO BACKEND
// ========================================
$(document).on("click", ".reaction-choice", function () {

    $.post("react_message_client.php", {
        chat_id: reactingToMsgId,
        emoji: $(this).data("emoji")
    }, () => {
        updateReactionBar(reactingToMsgId);
    });

    $("#reaction-picker").removeClass("show");
});

// ========================================
// REFRESH REACTION BAR (after click)
// ========================================
function updateReactionBar(msgID) {

    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);

        // Find the updated message in fresh HTML
        const updated = temp.find(`.message[data-msg-id='${msgID}']`)
                            .find(".reaction-bar");

        // Replace only the reaction bar in current DOM
        const current = $(`.message[data-msg-id='${msgID}']`)
                        .find(".reaction-bar");

        if (current.length) {
            current.replaceWith(updated.clone());
        } else {
            // If reaction bar does not exist yet → append it
            $(`.message[data-msg-id='${msgID}'] .message-content`)
                .append(updated.clone());
        }
    });
}

// ========================================
// GLOBAL CLICK → CLOSE REACTION PICKER
// ========================================
$(document).on("click", function (e) {
    if (!$(e.target).closest("#reaction-picker, .react-btn").length)
        $("#reaction-picker").removeClass("show");
});
// ========================================
// SkyTruFiber Chat System — PART 4 / 4
// ADVANCED LIGHTBOX VIEWER + GESTURES
// ========================================

// Internal viewer state
let lightboxScale = 1;
let lightboxTranslateX = 0;
let lightboxTranslateY = 0;
let isPanning = false;



const imgEl = document.getElementById("lightbox-image");
// ========================================
// MEDIA EVENTS — FINAL FULL VERSION
// ========================================
function attachMediaEvents() {

    // --------------------------------------------
    // IMAGE (Grid / Single) → OPEN LIGHTBOX
    // --------------------------------------------
    $(".media-grid img, .media-thumb").off("click").on("click", function () {

        const full = $(this).data("full");
        if (!full) return;

        // Build gallery from siblings inside same grid
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

    // --------------------------------------------
    // VIDEO CLICK (Grid or single video)
    // --------------------------------------------
    $(".media-grid video, .media-video").off("click").on("click", function () {

        const full = $(this).data("full");
        if (!full) return;

        resetLightboxTransform();
        $("#lightbox-image").hide();
        $("#lightbox-video").attr("src", full).show();

        $("#lightbox-overlay").addClass("show");
        updateLightboxIndex();
    });

    // --------------------------------------------
    // LIGHTBOX CONTROLS
    // --------------------------------------------
    $("#lightbox-prev").off("click").on("click", () => navigateGallery(-1));
    $("#lightbox-next").off("click").on("click", () => navigateGallery(1));
    $("#lightbox-close").off("click").on("click", closeLightbox);

    // --------------------------------------------
    // LIGHTBOX BACKGROUND CLICK (Smart Close)
    // --------------------------------------------
    $("#lightbox-overlay").off("click").on("click", function (e) {

        const isImg = $("#lightbox-image").is(":visible");

        if (isImg) {
            const img = document.getElementById("lightbox-image");
            const rect = img.getBoundingClientRect();

            // If clicking ON image → don't close
            const inside =
                e.clientX >= rect.left &&
                e.clientX <= rect.right &&
                e.clientY >= rect.top &&
                e.clientY <= rect.bottom;

            if (inside) return;
        }

        // Do NOT close if zoomed in
        if (lightboxScale > 1.02) return;

        closeLightbox();
    });

    // --------------------------------------------
    // ENSURE ACTION TOOLBAR POSITIONING
    // --------------------------------------------
    $(".message").each(function () {
        $(this).css("position", "relative");
    });

}


// ========================================
// OPEN IMAGE
// ========================================
function openImage(src) {
    resetLightboxTransform();

    $("#lightbox-video").hide();
    $("#lightbox-image").attr("src", src).show();

    $("#lightbox-overlay").addClass("show");
    updateLightboxIndex();
}

// ========================================
// UPDATE INDEX + DOWNLOAD URL
// ========================================
function updateLightboxIndex() {

    if (galleryItems.length > 1) {
        $("#lightbox-index")
            .text(`${currentIndex + 1} / ${galleryItems.length}`)
            .show();
    } else {
        $("#lightbox-index").hide();
    }

    const currentSrc = galleryItems[currentIndex];
    $("#lightbox-download").attr("href", currentSrc);
}

// ========================================
// SWIPE LEFT / RIGHT
// ========================================
function navigateGallery(step) {
    if (galleryItems.length <= 1) return;

    currentIndex = (currentIndex + step + galleryItems.length) % galleryItems.length;

    resetLightboxTransform();
    $("#lightbox-image").attr("src", galleryItems[currentIndex]);

    updateLightboxIndex();
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
// RESET TRANSFORM
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
// TOUCH GESTURES
// (Pinch zoom, pan drag, swipe left/right)
// ========================================
let touchStartDistance = 0;
let lastTouchX = 0;
let lastTouchY = 0;

let swipeStartX = 0;

// BLOCK page scroll when lightbox open
document.addEventListener("touchmove", function (e) {
    if ($("#lightbox-overlay").hasClass("show")) {
        e.preventDefault();
    }
}, { passive: false });

// Get finger distance (for pinch)
function getTouchDistance(ev) {
    const dx = ev.touches[0].clientX - ev.touches[1].clientX;
    const dy = ev.touches[0].clientY - ev.touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

// TOUCH START
imgEl.addEventListener("touchstart", function (ev) {

    if (ev.touches.length === 1) {
        isPanning = true;
        lastTouchX = ev.touches[0].clientX;
        lastTouchY = ev.touches[0].clientY;
        swipeStartX = lastTouchX;
    }
    else if (ev.touches.length === 2) {
        isPanning = false;
        touchStartDistance = getTouchDistance(ev);
    }

}, { passive: false });

// TOUCH MOVE
imgEl.addEventListener("touchmove", function (ev) {

    // PAN
    if (ev.touches.length === 1 && isPanning && lightboxScale > 1.02) {
        let x = ev.touches[0].clientX;
        let y = ev.touches[0].clientY;

        lightboxTranslateX += (x - lastTouchX);
        lightboxTranslateY += (y - lastTouchY);

        lastTouchX = x;
        lastTouchY = y;

        updateTransform();
    }

    // PINCH
    if (ev.touches.length === 2) {
        let newDist = getTouchDistance(ev);
        let ratio = newDist / touchStartDistance;

        let newScale = lightboxScale * ratio;

        newScale = Math.max(1, Math.min(newScale, 4)); // clamp

        lightboxScale = newScale;
        touchStartDistance = newDist;

        updateTransform();
    }

}, { passive: false });

// TOUCH END
imgEl.addEventListener("touchend", function (ev) {

    // SWIPE (only when NOT zoomed)
    if (ev.touches.length === 0 && lightboxScale <= 1.02) {
        const diff = lastTouchX - swipeStartX;

        if (Math.abs(diff) > 60) {
            if (diff > 0) navigateGallery(1);     // swipe left → next
            if (diff < 0) navigateGallery(-1);    // swipe right → prev
        }
    }

    // Reset if at natural zoom
    if (lightboxScale <= 1.02) {
        resetLightboxTransform();
    }

    isPanning = false;

}, { passive: false });

// ========================================
// DOUBLE TAP ZOOM
// ========================================
let lastTap = 0;

imgEl.addEventListener("touchend", function () {
    let now = Date.now();

    if (now - lastTap < 250) {
        // toggle zoom
        if (lightboxScale > 1.05) {
            resetLightboxTransform();
        } else {
            lightboxScale = 2;
            updateTransform();
        }
    }

    lastTap = now;
});

// ========================================
// DESKTOP — MOUSE WHEEL ZOOM
// ========================================
imgEl.addEventListener("wheel", function (ev) {
    ev.preventDefault();

    let zoom = -ev.deltaY * 0.001;
    lightboxScale += zoom;

    lightboxScale = Math.max(1, Math.min(lightboxScale, 4));

    updateTransform();

}, { passive: false });

// ========================================
// DESKTOP — DRAG TO PAN
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

// UPDATE CSS TRANSFORM
function updateTransform() {
    imgEl.style.transform =
        `translate(${lightboxTranslateX}px, ${lightboxTranslateY}px) scale(${lightboxScale})`;
}
