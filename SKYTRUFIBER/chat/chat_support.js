/* ===================================================================
   SkyTruFiber Chat System – FULL FIXED VERSION
   Messages • Upload • Reactions • Popups • Lightbox • Gestures
=================================================================== */

/* ------------------------------
   GLOBAL STATE
-------------------------------- */
let selectedFiles = [];
let lastMessageID = 0;
let editing = false;
let activePopup = null;
let reactingToMsgId = null;

let galleryItems = [];
let currentIndex = 0;

let lightboxScale = 1;
let lightboxTranslateX = 0;
let lightboxTranslateY = 0;
let isPanning = false;

const reactionChoices = ["👍","❤️","😂","😮","😢","😡"];
const username = new URLSearchParams(window.location.search).get("username");
const imgEl = document.getElementById("lightbox-image");

/* ------------------------------
   INIT
-------------------------------- */
$(document).ready(function () {

    if (!username) {
        $("#chat-messages").html("<p class='invalid-user'>Invalid user.</p>");
        return;
    }

    loadMessages(true);

    // polling
    setInterval(() => {
        if (!editing && !activePopup && !$("#preview-inline").is(":visible")) {
            fetchNewMessages();
        }
    }, 3500);

    // send button
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // media upload
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length > 0) previewMultiple(selectedFiles);
    });

    // theme toggle
    $("#theme-toggle").click(toggleTheme);

    // document click → close popups
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length)
            closePopup();

        if (!$(e.target).closest("#reaction-picker, .react-btn").length)
            $("#reaction-picker").removeClass("show");
    });
});

/* ------------------------------
   THEME SWITCH
-------------------------------- */
function toggleTheme() {
    const root = document.documentElement;
    let now = root.getAttribute("data-theme");
    root.setAttribute("data-theme", now === "dark" ? "light" : "dark");
}

/* ------------------------------
   SEND MESSAGE
-------------------------------- */
function sendMessage() {
    let msg = $("#message-input").val().trim();

    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);

    if (!msg) return;

    appendClientMessageInstant(msg);

    $.post("send_message_client.php", { username, message: msg }, () => {
        $("#message-input").val(""); // FIXED: clears input
        fetchNewMessages();
    });
}

// instant preview for sender
function appendClientMessageInstant(msg) {
    $("#chat-messages").append(`
        <div class="message sent">
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

/* ------------------------------
   FILE PREVIEW BEFORE UPLOAD
-------------------------------- */
$(document).on("click", ".preview-remove", function () {
    const index = $(this).data("i");
    selectedFiles.splice(index, 1);

    if (selectedFiles.length === 0)
        $("#preview-inline").slideUp(200);
    else
        previewMultiple(selectedFiles);
});

function previewMultiple(files) {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(150);

    files.forEach((file, i) => {
        let url = URL.createObjectURL(file);
        $("#preview-files").append(`
            <div class="preview-item">
                <img src="${url}" class="preview-thumb">
                <button class="preview-remove" data-i="${i}">&times;</button>
            </div>
        `);
    });
}

/* ------------------------------
   UPLOAD MEDIA
-------------------------------- */
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

/* ===================================================================
   PART 2 — MESSAGE LOADING + POPUP + EDIT + SCROLL
=================================================================== */

function loadMessages(scrollBottom = false) {
    $.post("load_messages_client.php", { username }, html => {

        $("#chat-messages").html(html);

        attachMediaEvents();
        bindReactionButtons();
        bindMoreButtons();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scrollBottom) scrollToBottom();
    });
}

function fetchNewMessages() {

    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);
        const incoming = temp.find(".message");

        const box = $("#chat-messages");
        const currentLast = box.find(".message:last").data("msg-id") || 0;

        incoming.each(function () {
            const id = $(this).data("msg-id");
            if (id > currentLast) box.append($(this));
        });

        attachMediaEvents();
        bindReactionButtons();
        bindMoreButtons();

        let distance = box[0].scrollHeight - box[0].scrollTop - box[0].clientHeight;

        if (distance < 130) scrollToBottom();
    });
}

/* ------------------------------
   SCROLL TO BOTTOM
-------------------------------- */
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 240);
}

$("#chat-messages").on("scroll", function () {
    const box = this;
    const distance = box.scrollHeight - box.scrollTop - box.clientHeight;

    if (distance > 70) $("#scroll-bottom-btn").addClass("show");
    else $("#scroll-bottom-btn").removeClass("show");
});

$("#scroll-bottom-btn").click(() => scrollToBottom());

/* ------------------------------
   POPUP MENU (...) FIXED
-------------------------------- */
function bindMoreButtons() {
    $(".more-btn").off("click").on("click", function (e) {
        e.stopPropagation();
        closePopup();

        const id = $(this).data("id");
        $("body").append(buildPopup(id));

        activePopup = $("#msg-action-popup");

        const pos = $(this).offset();
        activePopup.css({
            top: pos.top - activePopup.outerHeight() - 8,
            left: pos.left - (activePopup.outerWidth() / 2) + 16,
            zIndex: 999999
        }).fadeIn(120);
    });
}

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

function closePopup() {
    if (activePopup) {
        activePopup.fadeOut(120, () => activePopup.remove());
        activePopup = null;
    }
}

/* ------------------------------
   EDIT MESSAGE
-------------------------------- */
$(document).on("click", ".popup-edit", function () {
    const id = $(this).data("id");
    startEdit(id);
    closePopup();
});

function startEdit(id) {
    editing = true;
    const bubble = $(`.message[data-msg-id='${id}'] .message-bubble`);
    const original = bubble.text();

    bubble.html(`
        <textarea class="edit-textarea">${original}</textarea>
        <div class="edit-actions">
            <button class="edit-save" data-id="${id}">Save</button>
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

/* ------------------------------
   DELETE / UNSEND
-------------------------------- */
$(document).on("click", ".popup-delete, .popup-unsend", function () {
    $.post("delete_message_client.php", {
        id: $(this).data("id"),
        username
    }, () => loadMessages(false));
    closePopup();
});

$(document).on("click", ".popup-cancel", closePopup);

/* ===================================================================
   PART 3 — REACTIONS
=================================================================== */

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

function bindReactionButtons() {
    $(".react-btn").off("click").on("click", function (e) {
        e.stopPropagation();

        reactingToMsgId = $(this).data("msg-id");
        const picker = ensureReactionPicker();

        const pos = $(this).offset();

        picker.css({
            top: pos.top - picker.outerHeight() - 10,
            left: pos.left - picker.outerWidth() / 2 + 16,
            zIndex: 999999
        }).addClass("show");
    });
}

$(document).on("click", ".reaction-choice", function () {
    $.post("react_message_client.php", {
        chat_id: reactingToMsgId,
        emoji: $(this).data("emoji")
    }, () => updateReactionBar(reactingToMsgId));

    $("#reaction-picker").removeClass("show");
});

function updateReactionBar(msgID) {
    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);
        const newBar = temp.find(
            `.message[data-msg-id='${msgID}'] .reaction-bar`
        );

        const oldBar = $(`.message[data-msg-id='${msgID}'] .reaction-bar`);

        if (oldBar.length > 0)
            oldBar.replaceWith(newBar.clone());
        else
            $(`.message[data-msg-id='${msgID}'] .message-content`)
                .append(newBar.clone());
    });
}

/* ===================================================================
   PART 4 — LIGHTBOX + GALLERY (FIXED FULL VERSION)
=================================================================== */

function attachMediaEvents() {

    // IMAGE CLICK
    $(".media-grid img, .media-thumb").off("click").on("click", function () {

        const full = $(this).data("full");
        if (!full) return;

        const grid = $(this).closest(".media-grid");
        let items = grid.length ? grid.find("img") : $(this);

        galleryItems = [];
        currentIndex = 0;

        items.each(function (i) {
            const src = $(this).data("full");
            galleryItems.push(src);
            if (src === full) currentIndex = i;
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

    $("#lightbox-prev").off("click").on("click", () => navigateGallery(-1));
    $("#lightbox-next").off("click").on("click", () => navigateGallery(1));
    $("#lightbox-close").off("click").on("click", closeLightbox);

    $("#lightbox-overlay").off("click").on("click", e => {

        if ($("#lightbox-image").is(":visible")) {
            let rect = imgEl.getBoundingClientRect();
            const inside =
                e.clientX >= rect.left &&
                e.clientX <= rect.right &&
                e.clientY >= rect.top &&
                e.clientY <= rect.bottom;

            if (inside) return;
        }

        if (lightboxScale > 1.02) return;
        closeLightbox();
    });
}

function showLightboxImage(src) {
    resetLightboxTransform();
    $("#lightbox-video").hide();
    $("#lightbox-image").attr("src", src).show();
    $("#lightbox-overlay").addClass("show");
    updateLightboxIndex();
}

function navigateGallery(step) {
    if (!galleryItems.length) return;

    currentIndex = (currentIndex + step + galleryItems.length) % galleryItems.length;

    resetLightboxTransform();
    $("#lightbox-image").attr("src", galleryItems[currentIndex]);

    updateLightboxIndex();
}

function updateLightboxIndex() {
    if (galleryItems.length > 1) {
        $("#lightbox-index")
            .text(`${currentIndex + 1} / ${galleryItems.length}`)
            .show();
    } else $("#lightbox-index").hide();

    $("#lightbox-download").attr("href", galleryItems[currentIndex]);
}

function closeLightbox() {
    $("#lightbox-overlay").removeClass("show");
    $("#lightbox-image, #lightbox-video").hide();
    resetLightboxTransform();
}

function resetLightboxTransform() {
    lightboxScale = 1;
    lightboxTranslateX = 0;
    lightboxTranslateY = 0;
    imgEl.style.transform = "translate(0px,0px) scale(1)";
}

/* ------------------------------
   TOUCH + MOUSE ZOOM / PAN
-------------------------------- */
let touchStartDistance = 0;
let lastTouchX = 0;
let lastTouchY = 0;
let swipeStartX = 0;

document.addEventListener("touchmove", e => {
    if ($("#lightbox-overlay").hasClass("show"))
        e.preventDefault();
}, { passive:false });

function getTouchDistance(ev) {
    const dx = ev.touches[0].clientX - ev.touches[1].clientX;
    const dy = ev.touches[0].clientY - ev.touches[1].clientY;
    return Math.sqrt(dx*dx + dy*dy);
}

imgEl.addEventListener("touchstart", ev => {

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

}, { passive:false });

imgEl.addEventListener("touchmove", ev => {
    
    if (ev.touches.length === 1 && isPanning && lightboxScale > 1.02) {
        const x = ev.touches[0].clientX;
        const y = ev.touches[0].clientY;

        lightboxTranslateX += (x - lastTouchX);
        lightboxTranslateY += (y - lastTouchY);

        lastTouchX = x;
        lastTouchY = y;

        updateTransform();
    }

    if (ev.touches.length === 2) {
        const newDist = getTouchDistance(ev);
        let newScale = (newDist / touchStartDistance) * lightboxScale;
        
        newScale = Math.max(1, Math.min(4, newScale));
        
        lightboxScale = newScale;
        touchStartDistance = newDist;

        updateTransform();
    }

}, { passive:false });

imgEl.addEventListener("touchend", ev => {

    // swipe image to switch gallery
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

let lastTap = 0;
imgEl.addEventListener("touchend", ev => {
    let now = Date.now();

    if (now - lastTap < 260) {
        if (lightboxScale > 1.05) resetLightboxTransform();
        else {
            lightboxScale = 2;
            updateTransform();
        }
    }

    lastTap = now;
});

imgEl.addEventListener("wheel", ev => {
    ev.preventDefault();

    lightboxScale += -ev.deltaY * 0.001;
    lightboxScale = Math.max(1, Math.min(4, lightboxScale));

    updateTransform();
}, { passive:false });

// mouse pan
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

document.addEventListener("mouseup", () => mouseDown = false);

function updateTransform() {
    imgEl.style.transform =
        `translate(${lightboxTranslateX}px, ${lightboxTranslateY}px) scale(${lightboxScale})`;
}
