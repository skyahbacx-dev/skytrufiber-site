// ============================================================
// SkyTruFiber CSR Chat System — Updated 2025
// Features: Edit • Delete • Unsend • Media Upload • Carousel
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

    // UPLOAD MEDIA
    $("#upload-btn").click(() => $("#chat-upload-media").click());

    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // CLICK CLIENT
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
        }, 1500);
    });

    // SCROLL BUTTON
    $("#chat-messages").on("scroll", function () {
        const box = this;
        const dist = box.scrollHeight - box.scrollTop - box.clientHeight;
        if (dist > 70) $("#scroll-bottom-btn").addClass("show");
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
   SMOOTH SCROLL
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
   LOAD MESSAGES
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
   FETCH ONLY NEW MESSAGES (NO DUPLICATES)
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

        if (dist < 120) scrollToBottom();
    });
}

/* ============================================================
   SEND MESSAGE (TEXT OR MEDIA)
============================================================ */
function sendMessage() {

    const msg = $("#chat-input").val().trim();

    // Sending media
    if (selectedFiles.length > 0) {
        uploadMedia(selectedFiles, msg);
        return;
    }

    if (!msg || !currentClientID) return;

    appendTempBubble(msg);

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg
    }, (res) => {
        $(".temp-msg").remove();
        loadMessages(true);
        $("#chat-input").val("");
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
   MEDIA PREVIEW
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
    selectedFiles.length ? previewMultiple(selectedFiles) : $("#preview-inline").slideUp(200);
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
            loadMessages(true);
            $("#chat-input").val("");
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
    const offsetModal = $(".chat-wrapper").offset();

    popup.css({
        display: "block",
        top: offsetMsg.top - offsetModal.top + 28,
        left: offsetMsg.left - offsetModal.left - popup.outerWidth() + 30
    });

    activePopup = popup;
}

function closeActionPopup() {
    $("#msg-action-popup").hide();
    activePopup = null;
}

/* --- EDIT MESSAGE --- */
$(document).on("click", ".action-edit", function () {
    const id = $("#msg-action-popup").data("msg-id");
    startCSRMessageEdit(id);
    closeActionPopup();
});

function startCSRMessageEdit(id) {
    editing = true;

    const bubble = $(`.message[data-msg-id='${id}'] .message-bubble`);
    const oldText = bubble.text();

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

/* --- DELETE & UNSEND --- */
$(document).on("click", ".action-unsend, .action-delete", function () {

    const id = $("#msg-action-popup").data("msg-id");

    $.post("../chat/delete_message.php", { id }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});

/* ============================================================
   LIGHTBOX + CAROUSEL SUPPORT
============================================================ */
function attachMediaEvents() {

    // Image click → full screen
    $(document).off("click", ".fullview-item").on("click", ".fullview-item", function () {
        const src = $(this).attr("src");
        $("#lightbox-image").attr("src", src);
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").on("click", function (e) {
        if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close") {
            $("#lightbox-overlay").fadeOut(200);
        }
    });

    // Carousel navigation
    $(".carousel-arrow").off("click").on("click", function () {
        const group = $(this).data("group");
        const box = $(`.swipe-area[data-group="${group}"]`);
        const scroll = box.width() * 0.7;
        if ($(this).hasClass("left")) box.scrollLeft(box.scrollLeft() - scroll);
        else box.scrollLeft(box.scrollLeft() + scroll);
    });
}

/* ============================================================
   CLIENT ASSIGN HANDLERS
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
