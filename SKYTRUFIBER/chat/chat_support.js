// ========================================
// SkyTruFiber Client Chat System
// Chat UI + Edit + Unsend + Reactions + Popup Menu + Media + Upload Progress
// ========================================

let selectedFiles = [];
let lastMessageID = 0;
let loadInterval = null;
let galleryItems = [];
let currentIndex = 0;
let currentUploadXHR = null;
let reactingToMsgId = null;
let activePopup = null;        // track popup visibility
let editing = false;

const reactionChoices = ["👍", "❤️", "😂", "😮", "😢", "😡"];
const username = new URLSearchParams(window.location.search).get("username");

$(document).ready(function () {

    if (!username) {
        $("#chat-messages").html("<p style='padding:20px;text-align:center;color:#888;'>Invalid user.</p>");
        return;
    }

    loadMessages(true);

    // Auto refresh
    loadInterval = setInterval(() => {
        if (!$("#preview-inline").is(":visible") && !editing) loadMessages(false);
    }, 1200);

    // Send button
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) { e.preventDefault(); sendMessage(); }
    });

    // Upload files
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(function () {
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // ==========================
    // MORE (…) POPUP OPTIONS
    // ==========================
    $(document).on("click", ".more-btn", function (e) {
        e.stopPropagation();
        const msgID = $(this).data("id");

        closePopup();

        const btnOffset = $(this).offset();
        const popup = buildPopup(msgID);

        $("body").append(popup);

        const $popup = $("#msg-action-popup");
        $popup.css({
            top: btnOffset.top - $popup.outerHeight() - 5,
            left: btnOffset.left - ($popup.outerWidth() / 2) + 13
        }).fadeIn(120);

        activePopup = $popup;
    });

    $(document).on("click", ".popup-edit", function () {
        const msgID = $(this).data("id");
        startEdit(msgID);
        closePopup();
    });

    $(document).on("click", ".popup-unsend", function () {
        const msgID = $(this).data("id");
        $.post("delete_message_client.php", { id: msgID }, () => loadMessages(true));
        closePopup();
    });

    $(document).on("click", ".popup-delete", function () {
        const msgID = $(this).data("id");
        $.post("delete_message_client.php", { id: msgID }, () => loadMessages(true));
        closePopup();
    });

    // close popup anywhere
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) {
            closePopup();
        }
    });

    // ==========================
    // EDIT MESSAGE MODE
    // ==========================
    function startEdit(msgID) {
        editing = true;
        const messageDiv = $(`.message[data-msg-id='${msgID}']`);
        const bubble = messageDiv.find(".message-bubble");
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
        const val = $(this).closest(".message-bubble").find(".edit-textarea").val().trim();
        $.post("edit_message_client.php", { id: msgID, message: val }, () => {
            editing = false;
            loadMessages(true);
        });
    });

    $(document).on("click", ".edit-cancel", function () {
        editing = false;
        loadMessages(false);
    });

    // ==========================
    // REACTIONS
    // ==========================
    $(document).on("click", ".react-btn", function (e) {
        e.stopPropagation();
        reactingToMsgId = $(this).data("msg-id");

        const $picker = ensureReactionPicker();
        const btnOffset = $(this).offset();
        const pickerWidth = $picker.outerWidth();
        const pickerHeight = $picker.outerHeight();
        const btnWidth = $(this).outerWidth();

        $picker.css({
            top: btnOffset.top - pickerHeight - 10,
            left: btnOffset.left - (pickerWidth / 2) + (btnWidth / 2)
        }).addClass("show");
    });

    $(document).on("click", ".reaction-choice", function () {
        $.post("react_message_client.php", {
            chat_id: reactingToMsgId,
            emoji: $(this).data("emoji")
        }, () => loadMessages(false));

        $("#reaction-picker").removeClass("show");
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("#reaction-picker,.react-btn").length) {
            $("#reaction-picker").removeClass("show");
        }
    });

});

// ==========================
// POPUP BUILDER
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
        activePopup.fadeOut(120, () => activePopup.remove());
        activePopup = null;
    }
}

// ==========================
// REACTION PICKER
// ==========================
function ensureReactionPicker() {
    let $picker = $("#reaction-picker");
    if ($picker.length) return $picker;

    $("body").append(`
        <div id="reaction-picker" class="reaction-picker">
            ${reactionChoices.map(e => `<button class="reaction-choice" data-emoji="${e}">${e}</button>`).join("")}
        </div>
    `);

    return $("#reaction-picker");
}

// ==========================
// MESSAGE LOADING
// ==========================
function loadMessages(scrollBottom = false) {
    $.post("load_messages_client.php", { username }, html => {
        $("#chat-messages").html(html);    // full rerender
        const msgs = $("#chat-messages .message");
        if (!msgs.length) return;

        lastMessageID = parseInt(msgs.last().attr("data-msg-id")) || 0;
        if (scrollBottom) scrollToBottom();
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
    }, "json");
}

// ==========================
// INSTANT DOM
// ==========================
function appendClientMessageInstant(msg) {
    $("#chat-messages").append(`
        <div class="message sent fadeup">
            <div class="message-avatar"><img src="/upload/default-avatar.png"></div>
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
                <div class="message-time">now</div>
            </div>
        </div>
    `);
    scrollToBottom();
}

// ==========================
// MEDIA UPLOAD + PREVIEW + CANCEL
// ==========================
function previewMultiple(files) {
    $("#preview-files").html("");
    $("#preview-inline").slideDown(200);

    files.forEach((file, index) => {
        $("#preview-files").append(`
            <div class="preview-item">
                ${file.type.startsWith("image")
                ? `<img src="${URL.createObjectURL(file)}" class="preview-thumb">`
                : `<div class="file-box">📎 ${file.name}</div>`}
                <button class="preview-remove" data-i="${index}">&times;</button>
            </div>
        `);
    });
}

$(document).on("click", ".preview-remove", function () {
    selectedFiles.splice($(this).data("i"), 1);
    if (selectedFiles.length) previewMultiple(selectedFiles);
    else $("#preview-inline").slideUp(200);
});

// Upload system omitted (remains same from your latest version)

// ==========================
// SCROLL
// ==========================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 250);
}
