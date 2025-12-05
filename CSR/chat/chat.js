// ============================================================
// SkyTruFiber CSR Chat System — 2025 Full Updated Version
// Includes: Messenger-Style Media Grid • Edit • Delete • Unsend
// ============================================================

// ---------------- GLOBAL STATE ----------------
let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let selectedFiles = [];
let lastMessageID = 0;
let editing = false;
let activePopup = null;

$(document).ready(function () {

    loadClients();
    clientRefreshInterval = setInterval(loadClients, 4000);

    // SEARCH CLIENT
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // SEND MESSAGE
    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // MEDIA UPLOAD
    $("#upload-btn").click(() => $("#chat-upload-media").click());

    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // SELECT A CLIENT
    $(document).on("click", ".client-item", function () {
        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));

        $("#chat-messages").html("");
        lastMessageID = 0;

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(() => {
            if (!editing && !$("#preview-inline").is(":visible")) {
                fetchNewMessages();
            }
        }, 1200);
    });

    // SCROLL BUTTON APPEAR
    $("#chat-messages").on("scroll", function () {
        const box = this;
        const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

        if (dist > 80) $("#scroll-bottom-btn").addClass("show");
        else $("#scroll-bottom-btn").removeClass("show");
    });

    $("#scroll-bottom-btn").click(scrollToBottom);

    // CLOSE POPUP
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) {
            closeActionPopup();
        }
    });
});

/* ============================================================
   SCROLL TO BOTTOM
============================================================ */
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 220);
}

/* ============================================================
   LOAD CLIENTS + INFO
============================================================ */
function loadClients() {
    $.post("../chat/load_clients.php", function (html) {
        $("#client-list").html(html);
    });
}

function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, function (html) {
        $("#client-info-content").html(html);
    });
}

/* ============================================================
   LOAD ALL MESSAGES
============================================================ */
function loadMessages(scrollBottom = false) {
    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {

        $("#chat-messages").html(html);

        bindActionButtons();
        attachMediaEvents();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scrollBottom) scrollToBottom();
    });
}

/* ============================================================
   FETCH NEW MESSAGES ONLY
============================================================ */
function fetchNewMessages() {
    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {

        const temp = $("<div>").html(html);
        const incoming = temp.find(".message");

        incoming.each(function () {

            const id = $(this).data("msg-id");

            if ($(`.message[data-msg-id='${id}']`).length) return;

            $("#chat-messages").append($(this));
        });

        bindActionButtons();
        attachMediaEvents();

        const box = $("#chat-messages")[0];
        const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

        if (dist < 150) scrollToBottom();
    });
}

/* ============================================================
   SEND MESSAGE
============================================================ */
function sendMessage() {

    const msg = $("#chat-input").val().trim();

    // sending media → go file upload flow
    if (selectedFiles.length > 0) {
        uploadMedia(selectedFiles, msg);
        return;
    }

    if (!msg || !currentClientID) return;

    appendTempBubble(msg);

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg
    }, () => {
        $(".temp-msg").remove();
        $("#chat-input").val("");
        loadMessages(true);
    }, "json");
}

/* ============================================================
   TEMPORARY SENDING BUBBLE
============================================================ */
function appendTempBubble(msg) {
    $("#chat-messages").append(`
        <div class="message sent temp-msg">
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
                <div class="message-time">Sending...</div>
            </div>
        </div>
    `);
    scrollToBottom();
}

/* ============================================================
   MEDIA PREVIEW THUMBNAILS
============================================================ */
function previewMultiple(files) {
    $("#preview-files").html("");

    files.forEach((file, index) => {
        const url = URL.createObjectURL(file);

        $("#preview-files").append(`
            <div class="preview-item">
                <img src="${url}" class="preview-thumb">
                <button class="preview-remove" data-i="${index}">&times;</button>
            </div>
        `);
    });

    $("#preview-inline").slideDown(180);
}

$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);

    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});

/* ============================================================
   UPLOAD MEDIA FILES
============================================================ */
function uploadMedia(files, msg = "") {

    const form = new FormData();

    form.append("client_id", currentClientID);
    form.append("message", msg);

    files.forEach(f => form.append("media[]", f));

    $("#preview-inline").slideUp(150);
    selectedFiles = [];

    $.ajax({
        url: "../chat/media_upload.php",
        type: "POST",
        data: form,
        contentType: false,
        processData: false,
        success: () => {
            $("#chat-input").val("");
            loadMessages(true);
        }
    });
}

/* ============================================================
   ACTION MENU: EDIT / UNSEND / DELETE
============================================================ */
function bindActionButtons() {

    $(".more-btn").off("click").on("click", function (e) {
        e.stopPropagation();
        openActionPopup($(this).data("id"), this);
    });
}

function openActionPopup(id, anchor) {
    const popup = $("#msg-action-popup");
    popup.data("msg-id", id);

    const offsetMsg = $(anchor).offset();
    const offsetChat = $(".chat-wrapper").offset();

    popup.css({
        display: "block",
        top: offsetMsg.top - offsetChat.top + 30,
        left: offsetMsg.left - offsetChat.left - popup.outerWidth() + 40
    });

    activePopup = popup;
}

function closeActionPopup() {
    $("#msg-action-popup").hide();
    activePopup = null;
}

/* ------------ EDIT MESSAGE ------------- */
$(document).on("click", ".action-edit", function () {
    const id = $("#msg-action-popup").data("msg-id");
    startCSRMessageEdit(id);
    closeActionPopup();
});

function startCSRMessageEdit(id) {
    editing = true;

    const bubble = $(`.message[data-msg-id='${id}'] .message-bubble`);
    const oldText = bubble.text().trim();

    bubble.html(`
        <textarea class="edit-textarea">${oldText}</textarea>
        <div class="edit-actions">
            <button class="edit-save" data-id="${id}">Save</button>
            <button class="edit-cancel">Cancel</button>
        </div>
    `);
}

$(document).on("click", ".edit-save", function () {
    const id = $(this).data("id");
    const newText = $(this).closest(".message-bubble").find("textarea").val().trim();

    $.post("../chat/edit_message.php", { id, message: newText }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", function () {
    editing = false;
    loadMessages(false);
});

/* ------------ DELETE / UNSEND ----------- */
$(document).on("click", ".action-unsend, .action-delete", function () {
    const id = $("#msg-action-popup").data("msg-id");

    $.post("../chat/delete_message.php", { id }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});

/* ============================================================
   MEDIA FULL VIEW + OVERLAY + GRID
============================================================ */
function attachMediaEvents() {

    // Fullscreen image / video
    $(document).off("click", ".fullview-item").on("click", ".fullview-item", function () {

        const src = $(this).attr("src") || $(this).attr("data-full");

        $("#lightbox-image").attr("src", src);
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").on("click", function (e) {
        if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close") {
            $("#lightbox-overlay").fadeOut(200);
        }
    });
}

/* ============================================================
   CLIENT ASSIGN
============================================================ */
function assignClient(id) {
    $.post("../chat/assign_client.php", { client_id: id }, function () {
        loadClients();
        if (id === currentClientID) loadClientInfo(id);
    });
}

function unassignClient(id) {
    $.post("../chat/unassign_client.php", { client_id: id }, function () {
        loadClients();
        if (id === currentClientID)
            $("#client-info-content").html("<p>Select a client.</p>");
    });
}
// -------------------------------------------------------
// GLOBAL MEDIA SLIDESHOW STORAGE
// -------------------------------------------------------
let allMedia = [];          // [{src,type,index}, ...]
let currentSlide = 0;

// -------------------------------------------------------
// Build media array every time messages load
// -------------------------------------------------------
function collectAllMedia() {
    allMedia = [];

    $(".fullview-item").each(function () {
        const src = $(this).attr("data-full") || $(this).attr("src");
        const type = this.tagName.toLowerCase() === "video" ? "video" : "image";
        const index = parseInt($(this).attr("data-media-index"));

        allMedia.push({ src, type, index });
    });

    // Sort by index to maintain chronological order
    allMedia.sort((a, b) => a.index - b.index);
}

// -------------------------------------------------------
// Open Lightbox
// -------------------------------------------------------
$(document).on("click", ".fullview-item", function () {

    collectAllMedia();

    currentSlide = parseInt($(this).attr("data-media-index"));

    $("#lightbox-overlay").fadeIn(150);
    showSlide(currentSlide);
});

// -------------------------------------------------------
// Display selected slide
// -------------------------------------------------------
function showSlide(i) {
    const item = allMedia[i];
    if (!item) return;

    $(".lb-media").hide();

    if (item.type === "image") {
        $("#lightbox-image").attr("src", item.src).show();
    } else {
        $("#lightbox-video").attr("src", item.src).show();
    }

    currentSlide = i;
    rebuildThumbs();
}

// -------------------------------------------------------
// Next / Prev buttons
// -------------------------------------------------------
$("#lightbox-next").click(() => {
    if (currentSlide < allMedia.length - 1)
        showSlide(currentSlide + 1);
});

$("#lightbox-prev").click(() => {
    if (currentSlide > 0)
        showSlide(currentSlide - 1);
});

// -------------------------------------------------------
// Thumbnail bar
// -------------------------------------------------------
function rebuildThumbs() {

    $("#lightbox-thumbs").html("");

    allMedia.forEach((m, i) => {
        const active = (i === currentSlide) ? "thumb-active" : "";

        const el = `
            <img src="${m.src}" class="thumb ${active}" data-i="${i}">
        `;

        $("#lightbox-thumbs").append(el);
    });
}

$(document).on("click", ".thumb", function () {
    showSlide(parseInt($(this).attr("data-i")));
});

// -------------------------------------------------------
// Close lightbox
// -------------------------------------------------------
$("#lightbox-close, #lightbox-overlay").click(function (e) {
    if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close") {
        $("#lightbox-overlay").fadeOut(150);
        $("#lightbox-video").trigger("pause");
    }
});
