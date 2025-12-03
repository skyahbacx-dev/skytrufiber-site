// ========================================
// SkyTruFiber Client Chat System (Stable Polling Version)
// ========================================

let selectedFiles = [];
let lastMessageID = 0;
let currentUploadXHR = null;
let editing = false;
let activePopup = null;
let reactingToMsgId = null;

const reactionChoices = ["👍", "❤️", "😂", "😮", "😢", "😡"];
const username = new URLSearchParams(window.location.search).get("username");

$(document).ready(function () {

    if (!username) {
        $("#chat-messages").html("<p style='padding:20px;text-align:center;color:#888;'>Invalid user.</p>");
        return;
    }

    loadMessages(true);

    // POLLING EVERY 4 SECONDS
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

    // Remove preview item
    $(document).on("click", ".preview-remove", function () {
        selectedFiles.splice($(this).data("i"), 1);
        if (selectedFiles.length) previewMultiple(selectedFiles);
        else $("#preview-inline").slideUp(200);
    });

    // CLOSE POPUP
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) closePopup();
        if (!$(e.target).closest("#reaction-picker,.react-btn").length) $("#reaction-picker").removeClass("show");
    });

    // Open Message Menu Popup
    $(document).on("click", ".more-btn", function (e) {
        e.stopPropagation();

        const msgID = $(this).data("id");
        closePopup();

        const popupHTML = buildPopup(msgID);
        $("body").append(popupHTML);
        activePopup = $("#msg-action-popup");

        const pos = $(this).offset();
        activePopup.css({
            top: pos.top - activePopup.outerHeight() - 8,
            left: pos.left - (activePopup.outerWidth() / 2) + 15
        }).fadeIn(120);
    });

    // POPUP Actions
    $(document).on("click", ".popup-edit", function () {
        startEdit($(this).data("id"));
        closePopup();
    });

    $(document).on("click", ".popup-unsend", function () {
        $.post("delete_message_client.php", { id: $(this).data("id"), username }, () => loadMessages(false));
        closePopup();
    });

    $(document).on("click", ".popup-delete", function () {
        $.post("delete_message_client.php", { id: $(this).data("id"), username }, () => loadMessages(false));
        closePopup();
    });

    $(document).on("click", ".popup-cancel", closePopup);

    // EDIT Save / Cancel
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
        $.post("react_message_client.php", { chat_id: reactingToMsgId, emoji: $(this).data("emoji") }, () => {
            fetchNewMessages();
        });
        $("#reaction-picker").removeClass("show");
    });

});

// ==========================
// MESSAGE LOADING
// ==========================
function loadMessages(scrollBottom = false) {
    $.post("load_messages_client.php", { username }, html => {
        $("#chat-messages").html(html);
        attachMediaEvents();
        if (scrollBottom) scrollToBottom();

        const last = $("#chat-messages .message:last").data("msg-id");
        if (last) lastMessageID = last;
    });
}

// Only append new messages
function fetchNewMessages() {
    $.post("load_messages_client.php", { username }, html => {
        const temp = $("<div>").html(html);
        const newMessages = temp.find(".message");

        const container = $("#chat-messages");
        const currentLast = container.find(".message:last").data("msg-id") || 0;

        newMessages.each(function () {
            const id = $(this).data("msg-id");
            if (id > currentLast) {
                container.append($(this));
            }
        });

        attachMediaEvents();
        scrollToBottom();
    });
}

// ==========================
// SEND MESSAGE
// ==========================
function sendMessage() {
    const msg = $("#message-input").val().trim();

    if (selectedFiles.length > 0) return uploadMedia(selectedFiles, msg);
    if (!msg) return;

    appendClientMessageInstant(msg);

    $.post("send_message_client.php", { message: msg, username }, () => {
        $("#message-input").val("");
        fetchNewMessages();
    });
}

// TEMPORARY DISPLAY BEFORE SERVER RESPONSE
function appendClientMessageInstant(msg) {
    $("#chat-messages").append(`
        <div class="message sent fadeup">
            <div class="message-avatar"><img src="/upload/default-avatar.png"></div>
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
            </div>
        </div>
    `);
    scrollToBottom();
}

// ==========================
// MEDIA PREVIEW
// ==========================
function previewMultiple(files) {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(150);

    files.forEach((file, i) => {
        const isImage = file.type.startsWith("image");
        const url = URL.createObjectURL(file);

        $("#preview-files").append(`
            <div class="preview-item">
                ${isImage ? `<img src="${url}" class="preview-thumb">`
                           : `<div class="file-box">📎 ${file.name}</div>`}
                <button class="preview-remove" data-i="${i}">&times;</button>
            </div>
        `);
    });
}

// Upload media
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

// ==========================
// MEDIA LIGHTBOX
// ==========================
function attachMediaEvents() {
    $(".media-thumb").off("click").on("click", function () {
        $("#lightbox-image").attr("src", $(this).data("full")).show();
        $("#lightbox-overlay").addClass("show");
    });

    $("#lightbox-close").off("click").on("click", function () {
        $("#lightbox-overlay").removeClass("show");
        $("#lightbox-image").hide();
    });
}

// ==========================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 200);
}

// ==========================
// POPUP MENU
// ==========================
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
        const popup = activePopup;
        activePopup = null;
        popup.fadeOut(120, function () {
            if ($(this).length) $(this).remove();
        });
    }
}

// ==========================
// REACTION PICKER
// ==========================
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
