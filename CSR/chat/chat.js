// ============================================================
// SkyTruFiber CSR Chat System — TEXT ONLY VERSION (No media uploads)
// ============================================================

// ---------------- GLOBAL STATE ----------------
let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let lastMessageID = 0;
let editing = false;

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

    // DISABLE UPLOAD BUTTON
    $("#upload-btn").remove(); 
    $("#chat-upload-media").remove();
    $("#preview-inline").remove();

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

    if (!isAssignedToMe || isLocked) {
        bar.addClass("disabled");
        input.prop("disabled", true);
        sendBtn.prop("disabled", true);

        input.attr("placeholder",
            isLocked ?
            "Client is locked — you can't send messages." :
            "Client is assigned to another CSR — you can't send messages."
        );
    } else {
        bar.removeClass("disabled");
        input.prop("disabled", false);
        sendBtn.prop("disabled", false);
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

        setTimeout(() => $("#chat-messages").addClass("chat-slide-in"), 10);

        bindActionButtons();

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

        const box = $("#chat-messages")[0];
        const dist = box.scrollHeight - box.clientHeight - box.scrollTop;
        if (dist < 150) scrollToBottom();
    });
}


// ============================================================
// SEND TEXT MESSAGE ONLY
// ============================================================
function sendMessage() {
    const msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID) return;

    appendTempBubble(msg);

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg
    })
    .done(() => {
        $("#chat-input").val("");
        fetchNewMessages();
    })
    .fail(() => {
        $(".temp-msg .message-time").text("Failed");
    });
}

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
// ACTION POPUP (Edit, Delete)
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
// CLIENT ASSIGN / UNASSIGN
// ============================================================
$(document).on("click", ".add-client", function (e) {
    e.stopPropagation();
    let id = $(this).data("id");
    assignClient(id);
});

$(document).on("click", ".remove-client", function (e) {
    e.stopPropagation();
    let id = $(this).data("id");
    unassignClient(id);
});

function assignClient(id) {
    $.post("../chat/assign_client.php", { client_id: id }, () => {
        loadClients();
        setTimeout(() => $(`.client-item[data-id='${id}']`).click(), 120);
        loadClientInfo(id);
    });
}

function unassignClient(id) {
    $.post("../chat/unassign_client.php", { client_id: id }, () => {

        loadClients();

        if (id === currentClientID) {
            currentClientID = null;
            $("#chat-client-name").text("Select a Client");
            $("#chat-messages").html("");
            $("#client-info-content").html("<p>Select a client.</p>");
        }
    });
}
