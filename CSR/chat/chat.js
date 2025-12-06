// ============================================================
// SkyTruFiber CSR Chat System — Updated 2025 Stable Build
// Includes:
// - Permission Fix
// - Smooth Assign Animation (+ → -)
// - Auto-switch to Assigned View
// - Bubble Improvements
// ============================================================

// ---------------- GLOBAL STATE ----------------
let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let selectedFiles = [];
let lastMessageID = 0;
let editing = false;

// Global Lightbox State
let allMedia = [];
let currentSlide = 0;

// ============================================================
// DOCUMENT READY
// ============================================================
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
        if (e.which === 13) { e.preventDefault(); sendMessage(); }
    });

    // MEDIA UPLOAD
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;
        selectedFiles = Array.from(this.files);
        previewMultiple(selectedFiles);
    });

    // SELECT CLIENT
    $(document).on("click", ".client-item", function () {

        $(".client-item").removeClass("active-client");
        $(this).addClass("active-client");

        currentClientID = $(this).data("id");

        $("#chat-client-name").text($(this).data("name"));
        $("#chat-messages").html("");
        lastMessageID = 0;

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(fetchNewMessages, 1500);
    });

    // Scroll button
    $("#chat-messages").on("scroll", function () {
        const dist = this.scrollHeight - this.scrollTop - this.clientHeight;
        $("#scroll-bottom-btn").toggleClass("show", dist > 80);
    });

    $("#scroll-bottom-btn").click(scrollToBottom);

    // Close popup
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) {
            closeActionPopup();
        }
    });

    // Lightbox keys
    $(document).on("keydown", function(e){
        if ($("#lightbox-overlay").is(":visible")) {
            if (e.key === "Escape") closeLightbox();
            if (e.key === "ArrowLeft") showPrev();
            if (e.key === "ArrowRight") showNext();
        }
    });
});

// ============================================================
// SCROLL
// ============================================================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 200);
}

// ============================================================
// LOAD CLIENTS
// ============================================================
function loadClients() {
    $.post("../chat/load_clients.php", html => {
        $("#client-list").html(html);

        // Keep highlight on selected client
        if (currentClientID) {
            $(`.client-item[data-id='${currentClientID}']`).addClass("active-client");
        }
    });
}

// ============================================================
// LOAD CLIENT INFO
// ============================================================
function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, html => {
        $("#client-info-content").html(html);

        const meta = $("#client-meta");

        if (meta.length) {
            const isAssignedToMe = meta.data("assigned") === "yes";
            const isLocked = String(meta.data("locked")) === "true";

            handleChatPermission(isAssignedToMe, isLocked);
        } else {
            // Fallback: enable chat
            handleChatPermission(true, false);
        }
    });
}

// ============================================================
// ENABLE / DISABLE CHAT INPUT
// ============================================================
function handleChatPermission(isAssignedToMe, isLocked) {

    const bar = $(".chat-input-area");
    const input = $("#chat-input");
    const sendBtn = $("#send-btn");
    const uploadBtn = $("#upload-btn");

    if (!isAssignedToMe || isLocked) {
        bar.addClass("disabled");
        input.prop("disabled", true);
        sendBtn.prop("disabled", true);
        uploadBtn.prop("disabled", true);

        input.attr("placeholder",
            isLocked ?
            "Client is locked — you can't send messages." :
            "Client is assigned to another CSR or unassigned — you can't send messages."
        );

    } else {
        bar.removeClass("disabled");
        input.prop("disabled", false);
        sendBtn.prop("disabled", false);
        uploadBtn.prop("disabled", false);
        input.attr("placeholder", "Type a message...");
    }
}

// ============================================================
// LOAD MESSAGES
// ============================================================
function loadMessages(scrollBottom = false) {

    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {

        $("#chat-messages")
            .removeClass("chat-slide-in")
            .html(html);

        // trigger animation
        setTimeout(() => {
            $("#chat-messages").addClass("chat-slide-in");
        }, 10);

        bindActionButtons();
        assignMediaIndex();
        attachMediaEvents();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;

        if (scrollBottom) scrollToBottom();
    });
}

// ============================================================
// POLLING — NEW MESSAGES
// ============================================================
function fetchNewMessages() {

    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {

        const temp = $("<div>").html(html);
        const incoming = temp.find(".message");

        incoming.each(function () {
            const id = $(this).data("msg-id");
            if ($(`.message[data-msg-id='${id}']`).length) return;
            $("#chat-messages").append($(this));
        });

        bindActionButtons();
        assignMediaIndex();
        attachMediaEvents();

        const box = $("#chat-messages")[0];
        const dist = box.scrollHeight - box.clientHeight - box.scrollTop;
        if (dist < 150) scrollToBottom();
    });
}

// ============================================================
// SEND MESSAGE
// ============================================================
function sendMessage() {

    const msg = $("#chat-input").val().trim();

    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);
    if (!msg || !currentClientID) return;

    appendTempBubble(msg);

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg
    })
    .done(() => {
        $("#chat-input").val("");
        fetchNewMessagesSmooth();
    })
    .fail(() => {
        $(".temp-msg .message-time").text("Failed");
    });
}

// ============================================================
// TEMP BUBBLE
// ============================================================
function appendTempBubble(msg) {
    $("#chat-messages").append(`
        <div class="message sent temp-msg">
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
                <div class="message-time">Sending...</div>
            </div>
        </div>`);
    scrollToBottom();
}

// ============================================================
// MEDIA PREVIEW & UPLOAD
// ============================================================
function previewMultiple(files) {
    $("#preview-files").html("");

    files.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        $("#preview-files").append(`
            <div class="preview-item">
                <img src="${url}" class="preview-thumb">
                <button class="preview-remove" data-i="${index}">&times;</button>
            </div>`);
    });

    $("#preview-inline").slideDown(180);
}

$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    selectedFiles.length ? previewMultiple(selectedFiles) : $("#preview-inline").slideUp(200);
});

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
        processData: false
    })
    .done(() => {
        $("#chat-input").val("");
        fetchNewMessagesSmooth();
    });
}

// ============================================================
// ACTION POPUP
// ============================================================
function bindActionButtons() {

    $(".more-btn").off("click").on("click", function (e) {
        e.stopPropagation();
        openActionPopup($(this).data("id"), this);
    });
}

function openActionPopup(id, anchor) {

    const popup = $("#msg-action-popup");
    popup.data("msg-id", id);

    const bubble = $(anchor).closest(".message-content");
    const bubbleOffset = bubble.offset();
    const bubbleWidth = bubble.outerWidth();
    const chatOffset = $(".chat-wrapper").offset();

    let top = bubbleOffset.top - chatOffset.top - popup.outerHeight() - 10;
    let left = bubbleOffset.left - chatOffset.left + bubbleWidth - popup.outerWidth();

    popup.css({ top, left, display: "block" });

    const viewportWidth = $(window).width();
    const popupWidth = popup.outerWidth();

    popup.removeClass("flip-left");

    // Flip if overflow
    if (left + popupWidth > viewportWidth - 20) {
        left = bubbleOffset.left - chatOffset.left;
        popup.css({ left });
        popup.addClass("flip-left");
    }

    popup.addClass("show");
}

function closeActionPopup() {
    $("#msg-action-popup").removeClass("show").hide();
}

// ============================================================
// EDIT MESSAGE
// ============================================================
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
        </div>`);
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

// ============================================================
// DELETE MESSAGE
// ============================================================
$(document).on("click", ".action-unsend, .action-delete", function () {

    const id = $("#msg-action-popup").data("msg-id");

    $.post("../chat/delete_message.php", { id }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});

// ============================================================
// MEDIA INDEXING + LIGHTBOX
// ============================================================
function assignMediaIndex() {
    let index = 0;
    $(".fullview-item").each(function () {
        $(this).attr("data-media-index", index++);
    });
}

function collectAllMedia() {

    allMedia = [];

    $(".fullview-item").each(function () {
        let src = $(this).attr("data-full") || $(this).attr("src");
        let type = this.tagName.toLowerCase() === "video" ? "video" : "image";
        let index = parseInt($(this).attr("data-media-index"));
        allMedia.push({ src, type, index });
    });

    allMedia.sort((a, b) => a.index - b.index);
}

function attachMediaEvents() {

    $(document).off("click", ".fullview-item");

    $(document).on("click", ".fullview-item", function () {

        collectAllMedia();
        currentSlide = parseInt($(this).attr("data-media-index"));

        $("#lightbox-overlay").fadeIn(150);
        showSlide(currentSlide);
    });
}

function showSlide(i) {

    const item = allMedia[i];
    if (!item) return;

    $(".lb-media").hide();
    $("#lightbox-video").trigger("pause");

    if (item.type === "image") {
        $("#lightbox-image").attr("src", item.src).show();
    } else {
        $("#lightbox-video").attr("src", item.src).show();
    }

    currentSlide = i;
    rebuildThumbs();
}

function showNext() {
    if (currentSlide < allMedia.length - 1) showSlide(currentSlide + 1);
}

function showPrev() {
    if (currentSlide > 0) showSlide(currentSlide - 1);
}

$("#lightbox-next").click(showNext);
$("#lightbox-prev").click(showPrev);

function rebuildThumbs() {

    $("#lightbox-thumbs").html("");

    allMedia.forEach((m, i) => {
        $("#lightbox-thumbs").append(`
            <img src="${m.src}"
                 class="thumb ${i === currentSlide ? "thumb-active" : ""}"
                 data-i="${i}">`);
    });
}

$(document).on("click", ".thumb", function () {
    showSlide(parseInt($(this).data("i")));
});

function closeLightbox() {
    $("#lightbox-overlay").fadeOut(150);
    $("#lightbox-video").trigger("pause");
}

$("#lightbox-close, #lightbox-overlay").click(function (e) {
    if (e.target.id === "lightbox-overlay" || e.target.id === "lightbox-close") {
        closeLightbox();
    }
});

// ============================================================
// CLIENT ASSIGN + UNASSIGN (WITH ANIMATION & AUTO-SELECT)
// ============================================================
$(document).on("click", ".add-client", function (e) {
    e.stopPropagation();

    let id = $(this).data("id");
    let btn = $(this);

    // smooth shrink
    btn.addClass("animating");

    assignClient(id);

    // update list visually
    setTimeout(() => {
        loadClients();

        // auto-switch to this client
        currentClientID = id;

        const row = $(`.client-item[data-id='${id}']`);
        row.addClass("active-client");

        $("#chat-client-name").text(row.data("name"));

        loadClientInfo(id);
        loadMessages(true);

        // fade in new (-) button
        setTimeout(() => {
            $(".client-item[data-id='" + id + "'] .client-action-btn")
                .addClass("fade-in");
        }, 100);

    }, 200);
});


$(document).on("click", ".remove-client", function (e) {
    e.stopPropagation();

    let id = $(this).data("id");
    let btn = $(this);

    btn.addClass("animating");

    unassignClient(id);

    setTimeout(() => {
        loadClients();

        // If this CSR unassigned the current client → reset chat
        if (currentClientID === id) {
            currentClientID = null;
            $("#chat-client-name").text("Select a Client");
            $("#chat-messages").html("");
            $("#client-info-content").html("<p>Select a client.</p>");
        }

        setTimeout(() => {
            $(".client-item[data-id='" + id + "'] .client-action-btn")
                .addClass("fade-in");
        }, 100);

    }, 200);
});

function assignClient(id) {

    $.post("../chat/assign_client.php", { client_id: id }, () => {

        // Refresh sidebar instantly
        loadClients();

        // Auto-select the client you just assigned
        setTimeout(() => {
            $(`.client-item[data-id='${id}']`).click();
        }, 120);

        // Refresh permissions immediately
        loadClientInfo(id);
    });
}
function unassignClient(id) {

    $.post("../chat/unassign_client.php", { client_id: id }, () => {

        loadClients();

        // Remove active selection
        if (id === currentClientID) {
            currentClientID = null;
            $("#chat-client-name").text("Select a Client");
            $("#chat-messages").html("");
            $("#client-info-content").html("<p>Select a client.</p>");
        }
    });
}
