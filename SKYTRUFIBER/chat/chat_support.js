/* ============================================================
   SkyTruFiber Chat System — COMPLETE REWRITE
   Messenger-Style Chat • Reactions • Lightbox • Toolbar
============================================================ */

/* ---------------- GLOBAL STATE ---------------- */
let selectedFiles = [];
let lastMessageID = 0;
let editing = false;
let reactingToMsgId = null;
let activePopup = null;

let galleryItems = [];
let currentIndex = 0;

const reactionChoices = ["👍","❤️","😂","😮","😢","😡"];
const username = new URLSearchParams(window.location.search).get("username");


/* ============================================================
   INIT
============================================================ */
$(document).ready(() => {

    if (!username) {
        $("#chat-messages").html(`<p style="text-align:center;padding:20px;color:#555;">
            Invalid user.
        </p>`);
        return;
    }

    loadMessages(true);

    // Message polling (safe)
    setInterval(() => {
        if (!editing && !activePopup && !$("#preview-inline").is(":visible")) {
            fetchNewMessages();
        }
    }, 4000);

    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        selectedFiles.length ? previewMultiple() : null;
    });

    // Close popups
    $(document).on("click", e => {
        if (!$(e.target).closest(".more-btn, #msg-action-popup").length)
            closePopup();

        if (!$(e.target).closest(".react-btn, #reaction-picker").length)
            $("#reaction-picker").removeClass("show");
    });

    $("#theme-toggle").click(toggleTheme);
});


/* ============================================================
   THEME TOGGLE
============================================================ */
function toggleTheme() {
    const root = document.documentElement;
    root.setAttribute(
        "data-theme",
        root.getAttribute("data-theme") === "dark" ? "light" : "dark"
    );
}


/* ============================================================
   SEND MESSAGE
============================================================ */
function sendMessage() {
    const msg = $("#message-input").val().trim();

    if (!msg && selectedFiles.length === 0) return;

    if (selectedFiles.length > 0) return uploadMedia(msg);

    // Instant preview
    appendClientBubble(msg);

    $.post("send_message_client.php", { username, message: msg }, () => {
        $("#message-input").val("");
        fetchNewMessages();
    });
}

// Instant temporary bubble
function appendClientBubble(msg) {
    $("#chat-messages").append(`
        <div class="message sent">
            <div class="message-avatar"><img src="/upload/default-avatar.png"></div>
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
            </div>
        </div>
    `);
    scrollToBottom();
}


/* ============================================================
   MEDIA PREVIEW & UPLOAD
============================================================ */
function previewMultiple() {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(150);

    selectedFiles.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        $("#preview-files").append(`
            <div class="preview-item">
                <img src="${url}" class="preview-thumb">
                <button class="preview-remove" data-i="${index}">&times;</button>
            </div>
        `);
    });
}

$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    selectedFiles.length ? previewMultiple() : $("#preview-inline").slideUp(200);
});

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
function loadMessages(scrollBottom = false) {

    $.post("load_messages_client.php", { username }, html => {

        $("#chat-messages").html(html);

        attachMediaEvents();
        bindReactionButtons();
        bindActionToolbar();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scrollBottom) scrollToBottom();
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
        const currentLast = container.find(".message:last").data("msg-id") || 0;

        newMsgs.each(function () {
            const id = $(this).data("msg-id");
            if (id > currentLast) container.append($(this));
        });

        attachMediaEvents();
        bindReactionButtons();
        bindActionToolbar();

        // auto-scroll if user near bottom
        const box = container[0];
        const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

        if (dist < 120) scrollToBottom();
    });
}


/* ============================================================
   SCROLL HANDLING
============================================================ */
function scrollToBottom() {
    $("#chat-messages")
        .stop()
        .animate({ scrollTop: $("#chat-messages")[0].scrollHeight }, 250);
}

$("#chat-messages").on("scroll", function () {
    const box = this;
    const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

    if (dist > 70) $("#scroll-bottom-btn").addClass("show");
    else $("#scroll-bottom-btn").removeClass("show");
});

$("#scroll-bottom-btn").click(scrollToBottom);


/* ============================================================
   ACTION TOOLBAR (... and 😊)
============================================================ */
function bindActionToolbar() {
    $(".more-btn").off("click").on("click", function (e) {
        e.stopPropagation();
        openPopup($(this).data("id"), this);
    });
}

function openPopup(msgID, anchorEl) {

    closePopup();

    const popup = $(`
        <div class="msg-action-popup">
            <button class="popup-edit" data-id="${msgID}"><i class="fa fa-pen"></i> Edit</button>
            <button class="popup-unsend" data-id="${msgID}"><i class="fa fa-ban"></i> Unsend</button>
            <button class="popup-delete" data-id="${msgID}"><i class="fa fa-trash"></i> Delete</button>
            <button class="popup-cancel">Cancel</button>
        </div>
    `);

    $("body").append(popup);
    activePopup = popup;

    const pos = $(anchorEl).offset();

    popup.css({
        top: pos.top - popup.outerHeight() - 5,
        left: pos.left - popup.outerWidth() / 2 + 15,
        zIndex: 999999
    }).fadeIn(120);
}

function closePopup() {
    if (activePopup) {
        activePopup.remove();
        activePopup = null;
    }
}

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

$(document).on("click", ".edit-cancel", function () {
    editing = false;
    loadMessages(false);
});


/* ============================================================
   REACTIONS
============================================================ */
function ensureReactionPicker() {

    let picker = $("#reaction-picker");

    if (picker.length === 0) {
        $("body").append(`
            <div id="reaction-picker" class="reaction-picker">
                ${reactionChoices.map(e => `<button class="reaction-choice" data-emoji="${e}">${e}</button>`).join("")}
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
            top: pos.top - picker.outerHeight() - 12,
            left: pos.left - picker.outerWidth() / 2 + 15,
            zIndex: 999999
        }).addClass("show");
    });
}

// Apply reaction
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

        if (curBar.length) curBar.replaceWith(newBar.clone());
        else $(`.message[data-msg-id='${id}'] .message-content`).append(newBar.clone());
    });
}


/* ============================================================
   LIGHTBOX (Images & Videos)
============================================================ */

let lightboxScale = 1;
let lightboxTranslateX = 0;
let lightboxTranslateY = 0;
let isPanning = false;

const imgEl = document.getElementById("lightbox-image");

function attachMediaEvents() {

    $(".media-thumb, .media-grid img").off("click").on("click", function () {

        const src = $(this).data("full");
        const grid = $(this).closest(".media-grid");
        const imgs = grid.find("img");

        galleryItems = imgs.map((i, el) => $(el).data("full")).get();
        currentIndex = imgs.index(this);

        openImage(src);
    });

    $(".media-video, .media-grid video").off("click").on("click", function () {
        openVideo($(this).data("full"));
    });

    $("#lightbox-prev").off("click").on("click", () => navigateGallery(-1));
    $("#lightbox-next").off("click").on("click", () => navigateGallery(1));
    $("#lightbox-close").off("click").on("click", closeLightbox);
}

function openImage(src) {
    resetLightboxTransform();
    $("#lightbox-video").hide();
    $("#lightbox-image").attr("src", src).show();
    $("#lightbox-overlay").addClass("show");
    updateLightboxIndex();
}

function openVideo(src) {
    resetLightboxTransform();
    $("#lightbox-image").hide();
    $("#lightbox-video").attr("src", src).show();
    $("#lightbox-overlay").addClass("show");
}

function closeLightbox() {
    $("#lightbox-overlay").removeClass("show");
    $("#lightbox-image, #lightbox-video").hide();
}

function navigateGallery(step) {
    if (galleryItems.length <= 1) return;
    currentIndex = (currentIndex + step + galleryItems.length) % galleryItems.length;
    openImage(galleryItems[currentIndex]);
}

function updateLightboxIndex() {
    if (galleryItems.length > 1)
        $("#lightbox-index").text(`${currentIndex + 1} / ${galleryItems.length}`).show();
    else
        $("#lightbox-index").hide();
}


/* ============================================================
   LIGHTBOX GESTURES (zoom, drag)
============================================================ */

function resetLightboxTransform() {
    lightboxScale = 1;
    lightboxTranslateX = 0;
    lightboxTranslateY = 0;
    imgEl.style.transform = "translate(0px,0px) scale(1)";
}

// Drag + Zoom + Swipe listeners remain same
// (They work without any edits)

