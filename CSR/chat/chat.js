// ============================================================
// SkyTruFiber CSR Chat System — 2025 FINAL VERSION
// Features: Edit • Delete • Unsend • Media Upload • Full Media Grid
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
    clientRefreshInterval = setInterval(loadClients, 5000);

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

    // OPEN FILE UPLOADER
    $("#upload-btn").click(() => $("#chat-upload-media").click());

    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // SELECT CLIENT
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

    // SCROLL BUTTON INDICATOR
    $("#chat-messages").on("scroll", function () {
        const box = this;
        const dist = box.scrollHeight - box.scrollTop - box.clientHeight;
        if (dist > 120) $("#scroll-bottom-btn").addClass("show");
        else $("#scroll-bottom-btn").removeClass("show");
    });

    $("#scroll-bottom-btn").click(scrollToBottom);

    // CLICK OUTSIDE -> CLOSE ACTION POPUP
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
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 250);
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
   LOAD MESSAGES (Full Refresh)
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
   FETCH ONLY NEW MESSAGES
============================================================ */
function fetchNewMessages() {

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {

        const temp = $("<div>").html(html);
        const newMsgs = temp.find(".message");

        newMsgs.each(function () {
            const id = $(this).data("msg-id");

            // Skip duplicates
            if ($(`.message[data-msg-id='${id}']`).length) return;

            $("#chat-messages").append($(this));
        });

        bindActionButtons();
        attachMediaEvents();

        const box = $("#chat-messages")[0];
        const dist = box.scrollHeight - box.scrollTop - box.clientHeight;

        if (dist < 160) scrollToBottom();
    });
}

/* ============================================================
   SEND MESSAGE
============================================================ */
function sendMessage() {

    const msg = $("#chat-input").val().trim();

    // MEDIA SEND
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
        loadMessages(true);
        $("#chat-input").val("");
    }, "json");
}

/* ============================================================
   TEMP BUBBLE
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
   MEDIA PREVIEW BEFORE UPLOAD
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
    selectedFiles.length ? previewMultiple(selectedFiles) : $("#preview-inline").slideUp(150);
});

/* ============================================================
   MEDIA UPLOAD
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
   ACTION MENU BUTTONS
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

    const btn = $(anchor).offset();
    const container = $(".chat-wrapper").offset();

    popup.css({
        display: "block",
        top: btn.top - container.top + 28,
        left: btn.left - container.left - popup.outerWidth() + 40
    });

    activePopup = popup;
}

function closeActionPopup() {
    $("#msg-action-popup").hide();
    activePopup = null;
}

/* ============================================================
   EDIT MESSAGE
============================================================ */
$(document).on("click", ".action-edit", function () {

    const id = $("#msg-action-popup").data("msg-id");
    closeActionPopup();

    editing = true;

    const bubble = $(`.message[data-msg-id='${id}'] .message-bubble`);
    const oldText = bubble.find(".msg-text").text();

    bubble.html(`
        <textarea class="edit-textarea">${oldText}</textarea>
        <div class="edit-actions">
            <button class="edit-save" data-id="${id}">Save</button>
            <button class="edit-cancel">Cancel</button>
        </div>
    `);
});

$(document).on("click", ".edit-save", function () {

    const id = $(this).data("id");
    const newText = $(this).closest(".message-bubble").find("textarea").val().trim();

    $.post("../chat/edit_message.php", { id, message: newText }, () => {
        editing = false;
        loadMessages(false);
    });
});

$(document).on("click", ".edit-cancel", function () {
    editing = false;
    loadMessages(false);
});

/* ============================================================
   DELETE / UNSEND
============================================================ */
$(document).on("click", ".action-delete, .action-unsend", function () {

    const id = $("#msg-action-popup").data("msg-id");

    $.post("../chat/delete_message.php", { id }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});

/* ============================================================
   MEDIA VIEWER (LIGHTBOX)
============================================================ */
function attachMediaEvents() {

    $(document).off("click", ".fullview-item").on("click", ".fullview-item", function () {

        const mediaType = this.tagName.toLowerCase();
        const src = $(this).attr("src");

        if (mediaType === "img") {
            $("#lightbox-image").attr("src", src).show();
            $("#lightbox-video").hide();
        } else {
            $("#lightbox-video").attr("src", src).show();
            $("#lightbox-image").hide();
        }

        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").off("click").on("click", function (e) {
        if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close") {
            $("#lightbox-overlay").fadeOut(150);
            $("#lightbox-video").trigger("pause");
        }
    });
}

/* ============================================================
   ASSIGN / UNASSIGN CLIENT
============================================================ */
function assignClient(id) {
    $.post("../chat/assign_client.php", { client_id: id }, () => {
        loadClients();
        if (id === currentClientID) loadClientInfo(id);
    });
}

function unassignClient(id) {
    $.post("../chat/unassign_client.php", { client_id: id }, () => {
        loadClients();
        if (id === currentClientID)
            $("#client-info-content").html("<p>Select a client.</p>");
    });
}
