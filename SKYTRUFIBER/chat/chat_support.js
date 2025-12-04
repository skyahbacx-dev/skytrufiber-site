/* ============================================================
   SkyTruFiber Chat System — FINAL JS BUILD (Stabilized)
   Reactions • Media Grid • Lightbox V2 • Toolbar • Upload
============================================================ */

/* ---------------- GLOBAL STATE ---------------- */
let selectedFiles = [];
let lastMessageID = 0;
let editing = false;
let activePopup = null;
let reactingToMsgId = null;

let galleryItems = [];
let currentIndex = 0;

const reactionChoices = ["👍", "❤️", "😂", "😮", "😢", "😡"];
const username = new URLSearchParams(window.location.search).get("username");

/* ============================================================
   INIT
============================================================ */
$(document).ready(() => {

    if (!username) {
        $("#chat-messages").html(`<p style="text-align:center;padding:20px;color:#777;">Invalid user.</p>`);
        return;
    }

    loadMessages(true);

    // Polling
    setInterval(() => {
        if (!editing && !activePopup && !$("#preview-inline").is(":visible")) {
            fetchNewMessages();
        }
    }, 3500);

    // Send
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
        if (selectedFiles.length) previewMultiple();
    });

    // Close popups
    $(document).on("click", e => {

        if (!$(e.target).closest(".msg-action-popup, .more-btn").length) closePopup();

        if (!$(e.target).closest("#reaction-picker, .react-btn").length)
            $("#reaction-picker").removeClass("show");
    });

    // Theme toggle
    $("#theme-toggle").click(toggleTheme);
});

/* ============================================================
   THEME
============================================================ */
function toggleTheme() {
    const root = document.documentElement;
    const newTheme = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
    root.setAttribute("data-theme", newTheme);

    $("#theme-toggle").addClass("spin");
    setTimeout(() => $("#theme-toggle").removeClass("spin"), 400);
}

/* ============================================================
   SEND MESSAGE
============================================================ */
function sendMessage() {

    const msg = $("#message-input").val().trim();

    if (!msg && selectedFiles.length === 0) return;

    // Upload
    if (selectedFiles.length > 0) return uploadMedia(msg);

    appendClientBubble(msg);
    $("#message-input").val("");

    $.post("send_message_client.php", { username, message: msg }, () => {
        fetchNewMessages();
    });
}

function appendClientBubble(msg) {
    $("#chat-messages").append(`
        <div class="message sent no-avatar">
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
            </div>
        </div>
    `);
    scrollToBottom();
}

/* ============================================================
   PREVIEW MULTIPLE
============================================================ */
function previewMultiple() {
    $("#preview-inline").slideDown(150);
    $("#preview-files").html("");

    selectedFiles.forEach((file, i) => {
        const url = URL.createObjectURL(file);
        $("#preview-files").append(`
            <div class="preview-item">
                <img src="${url}" class="preview-thumb">
                <button class="preview-remove" data-i="${i}">&times;</button>
            </div>
        `);
    });
}

$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    selectedFiles.length ? previewMultiple() : $("#preview-inline").slideUp(200);
});

/* ============================================================
   UPLOAD MEDIA
============================================================ */
function uploadMedia(msg) {
    const form = new FormData();
    form.append("username", username);
    form.append("message", msg);

    selectedFiles.forEach(f => form.append("media[]", f));

    $.ajax({
        url: "upload_media_client.php",
        method: "POST",
        data: form,
        contentType: false,
        processData: false,
        success: () => {
            selectedFiles = [];
            $("#preview-inline").slideUp(200);
            $("#message-input").val("");
            fetchNewMessages();
        }
    });
}

/* ============================================================
   LOAD MESSAGES
============================================================ */
function loadMessages(scroll = false) {

    $.post("load_messages_client.php", { username }, html => {

        $("#chat-messages").html(html);

        bindReactionButtons();
        bindActionToolbar();
        attachMediaEvents();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scroll) scrollToBottom();
    });
}

/* ============================================================
   FETCH NEW MESSAGES
============================================================ */
function fetchNewMessages() {

    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);
        const newMsgs = temp.find(".message");

        const container = $("#chat-messages");
        const lastDisplayedID = container.find(".message:last").data("msg-id") || 0;

        newMsgs.each(function () {
            const id = $(this).data("msg-id");
            if (id > lastDisplayedID) container.append($(this));
        });

        bindReactionButtons();
        bindActionToolbar();
        attachMediaEvents();

        const box = container[0];
        const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

        if (dist < 120) scrollToBottom();
    });
}

/* ============================================================
   SCROLL
============================================================ */
function scrollToBottom() {
    $("#chat-messages").stop().animate({
        scrollTop: $("#chat-messages")[0].scrollHeight
    }, 230);
}

$("#scroll-bottom-btn").click(scrollToBottom);

$("#chat-messages").on("scroll", function () {

    const box = this;
    const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

    if (dist > 70) $("#scroll-bottom-btn").addClass("show");
    else $("#scroll-bottom-btn").removeClass("show");
});

/* ============================================================
   ACTION TOOLBAR / POPUP
============================================================ */
function bindActionToolbar() {
    $(".more-btn").off("click").on("click", function (e) {
        e.stopPropagation();
        openPopup($(this).data("id"), this);
    });
}

function openPopup(id, anchor) {

    closePopup();

    const popup = $(`
        <div class="msg-action-popup">
            <button class="popup-edit" data-id="${id}">Edit</button>
            <button class="popup-unsend" data-id="${id}">Unsend</button>
            <button class="popup-delete" data-id="${id}">Delete</button>
            <button class="popup-cancel">Cancel</button>
        </div>
    `);

    $(".chat-modal").append(popup);

    activePopup = popup;

    const a = $(anchor).offset();
    const modal = $(".chat-modal").offset();

    popup.css({
        top: a.top - modal.top - popup.outerHeight() - 6,
        left: a.left - modal.left - popup.outerWidth() / 2 + 20
    }).fadeIn(120);
}

function closePopup() {
    if (activePopup) {
        activePopup.remove();
        activePopup = null;
    }
}

/* Popup actions */
$(document).on("click", ".popup-edit", function () {
    startEdit($(this).data("id"));
    closePopup();
});

$(document).on("click", ".popup-unsend, .popup-delete", function () {
    $.post("delete_message_client.php",
        { id: $(this).data("id"), username },
        () => loadMessages(false)
    );
    closePopup();
});

$(document).on("click", ".popup-cancel", closePopup);

/* ============================================================
   EDIT MESSAGE
============================================================ */
function startEdit(id) {

    editing = true;

    const bubble = $(`.message[data-msg-id='${id}'] .message-bubble`);
    const old = bubble.text();

    bubble.html(`
        <textarea class="edit-textarea">${old}</textarea>
        <div class="edit-actions">
            <button class="edit-save" data-id="${id}">Save</button>
            <button class="edit-cancel">Cancel</button>
        </div>
    `);
}

$(document).on("click", ".edit-save", function () {

    const id = $(this).data("id");
    const newText = $(this).closest(".message-bubble").find("textarea").val().trim();

    $.post("edit_message_client.php", { id, message: newText }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", () => {
    editing = false;
    loadMessages(false);
});

/* ============================================================
   REACTIONS
============================================================ */
function ensureReactionPicker() {

    let picker = $("#reaction-picker");

    if (!picker.length) {
        $("body").append(`
            <div id="reaction-picker" class="reaction-picker">
                ${reactionChoices.map(e =>
                    `<button class="reaction-choice" data-emoji="${e}">${e}</button>`
                ).join("")}
            </div>
        `);
        picker = $("#reaction-picker");
    }

    return picker;
}

function bindReactionButtons() {
    $(".react-btn").off("click").on("click", function (e) {

        e.stopPropagation();

        reactingToMsgId = $(this).data("msg-id");

        const picker = ensureReactionPicker();
        const pos = $(this).offset();

        picker.css({
            top: pos.top - picker.outerHeight() - 10,
            left: pos.left - picker.outerWidth() / 2 + 15
        }).addClass("show");
    });
}

$(document).on("click", ".reaction-choice", function () {

    $.post("react_message_client.php",
        { chat_id: reactingToMsgId, emoji: $(this).data("emoji") },
        () => updateReactionBar(reactingToMsgId)
    );

    $("#reaction-picker").removeClass("show");
});

function updateReactionBar(id) {

    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);

        const newBar = temp.find(`.message[data-msg-id='${id}'] .reaction-bar`);
        const curBar = $(`.message[data-msg-id='${id}'] .reaction-bar`);

        if (curBar.length)
            curBar.replaceWith(newBar.clone());
        else
            $(`.message[data-msg-id='${id}'] .message-content`).append(newBar.clone());
    });
}

/* ============================================================
   LIGHTBOX V2
============================================================ */
const lbOverlay = document.getElementById("lightbox-overlay");
const lbImage = document.getElementById("lightbox-image");
const lbVideo = document.getElementById("lightbox-video");
const lbIndex = document.getElementById("lightbox-index");

let touchStartX = 0;

/* MEDIA BINDERS */
function attachMediaEvents() {

    $(".media-grid img, .media-thumb").off("click").on("click", function () {

        const src = $(this).data("full");

        const grid = $(this).closest(".media-grid");
        const imgs = grid.find("img");

        galleryItems = imgs.map((i, e) => ({
            type: "image",
            src: $(e).data("full")
        })).get();

        currentIndex = imgs.index(this);

        openImage(src);
    });

    $(".media-grid video").off("click").on("click", function () {
        galleryItems = [{ type: "video", src: $(this).data("full") }];
        currentIndex = 0;
        openVideo($(this).data("full"));
    });

    $("#lightbox-prev").off().click(() => navigateGallery(-1));
    $("#lightbox-next").off().click(() => navigateGallery(1));
    $("#lightbox-close").off().click(closeLightbox);
}

/* OPEN IMAGE / VIDEO */
function openImage(src) {
    lbVideo.style.display = "none";
    lbImage.style.display = "block";
    lbImage.src = src;
    lbOverlay.classList.add("show");
    updateLightboxIndex();
}

function openVideo(src) {
    lbImage.style.display = "none";
    lbVideo.style.display = "block";
    lbVideo.src = src;
    lbOverlay.classList.add("show");
}

/* CLOSE LIGHTBOX */
function closeLightbox() {
    lbOverlay.classList.remove("show");
    lbVideo.src = "";
    lbImage.src = "";
}

/* NAVIGATION */
function navigateGallery(step) {

    if (galleryItems.length <= 1) return;

    const el = lbImage.style.display !== "none" ? lbImage : lbVideo;

    el.classList.add(step === 1 ? "lightbox-slide-left" : "lightbox-slide-right");

    setTimeout(() => {

        currentIndex = (currentIndex + step + galleryItems.length) % galleryItems.length;

        const item = galleryItems[currentIndex];

        if (item.type === "image") openImage(item.src);
        else openVideo(item.src);

        el.classList.remove("lightbox-slide-left", "lightbox-slide-right");

    }, 180);

    updateLightboxIndex();
}

/* COUNTER */
function updateLightboxIndex() {
    if (galleryItems.length > 1) {
        lbIndex.style.display = "block";
        lbIndex.textContent = `${currentIndex + 1} / ${galleryItems.length}`;
    } else {
        lbIndex.style.display = "none";
    }
}

/* SWIPE */
lbOverlay.addEventListener("touchstart", e => {
    touchStartX = e.changedTouches[0].screenX;
});

lbOverlay.addEventListener("touchend", e => {
    const delta = e.changedTouches[0].screenX - touchStartX;
    if (delta < -60) navigateGallery(1);
    if (delta > 60) navigateGallery(-1);
});

/* KEYBOARD */
document.addEventListener("keydown", e => {
    if (!lbOverlay.classList.contains("show")) return;

    if (e.key === "ArrowLeft") navigateGallery(-1);
    if (e.key === "ArrowRight") navigateGallery(1);
    if (e.key === "Escape") closeLightbox();
});
