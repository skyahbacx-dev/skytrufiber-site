// ============================================================
// SkyTruFiber CSR Chat System — FINAL, HARDENED VERSION (STABLE)
// ============================================================

let currentClientID = null;
let currentTicketID = null;
let lastMessageID = 0;

let currentTicketFilter = "all";

let clientListInterval = null;
let messageInterval = null;
let clientInfoInterval = null;

let editingMessageId = null;

$(document).ready(function () {

    /* =======================
       INITIAL LOAD
    ======================= */
    loadClients();

    clientListInterval = setInterval(() => {
        loadClients(false);
    }, 6000);

    clientInfoInterval = setInterval(() => {
        if (currentClientID) loadClientInfo(currentClientID, false);
    }, 3000);

    /* =======================
       SEARCH
    ======================= */
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    /* =======================
       FILTERS
    ======================= */
    $(document).on("click", ".ticket-filter", function () {
        currentTicketFilter = $(this).data("filter");
        $(".ticket-filter").removeClass("active");
        $(this).addClass("active");
        loadClients();
    });

    /* =======================
       SELECT CLIENT
    ======================= */
    $(document).on("click", ".client-item", function (e) {

        if ($(e.target).closest(".assign-btn, .unassign-btn, .locked-icon").length) return;

        $(".client-item").removeClass("active-client");
        $(this).addClass("active-client");

        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));

        const chatBox = $("#chat-messages");

        chatBox
            .addClass("is-loading")
            .removeClass("is-ready")
            .empty();

        lastMessageID = 0;

        loadClientInfo(currentClientID, true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(fetchNewMessages, 1000);
    });

    /* =======================
       SEND MESSAGE
    ======================= */
    $("#send-btn").on("click", sendMessage);

    $("#chat-input").on("keypress", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    /* =======================
       SCROLL HANDLING
    ======================= */
    $("#chat-messages").on("scroll", function () {
        handleScrollButton();
        closeActionPopup();
    });

    $(document).on("click", ".scroll-bottom-btn", scrollToBottom);

    /* =======================
       CLOSE ACTION POPUP
    ======================= */
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .message-more-btn").length) {
            closeActionPopup();
        }
    });

    $("#msg-action-popup").on("click", function (e) {
        e.stopPropagation();
    });
});


/* ============================================================
   LOAD CLIENT LIST
============================================================ */
function loadClients(preserve = true) {
    $.post("/CSR/chat/load_clients.php", {
        filter: currentTicketFilter,
        nocache: Date.now()
    }, html => {
        $("#client-list").html(html);
        if (preserve && currentClientID) {
            $(`.client-item[data-id='${currentClientID}']`).addClass("active-client");
        }
    });
}


/* ============================================================
   LOAD CLIENT INFO
============================================================ */
function loadClientInfo(id, loadMessagesNow = false) {

    $.post("/CSR/chat/load_client_info.php", {
        client_id: id,
        nocache: Date.now()
    }, html => {

        $("#client-info-content").html(html);

        const meta = $("#client-meta");
        if (!meta.length) return;

        currentTicketID = parseInt(meta.data("ticket-id"), 10) || null;

        const assignedToMe = meta.data("assigned") === "yes";
        const locked = meta.data("locked") === "true";
        const ticketStatus = meta.data("ticket");

        const dropdown = $("#ticket-status-dropdown");

        dropdown
            .val(ticketStatus)
            .data("current", ticketStatus)
            .prop("disabled", !assignedToMe)
            .removeClass("unresolved pending resolved saving")
            .addClass(ticketStatus);

        handleChatPermission(assignedToMe, locked, ticketStatus);

        /* =======================
           RESOLVED — STOP CHAT
        ======================= */
        if (ticketStatus === "resolved") {
            $("#chat-messages")
                .removeClass("is-loading")
                .addClass("is-ready")
                .html(`
                    <div class="chat-resolved-notice">
                        <strong>This ticket has been resolved.</strong><br>
                        Chat history is available in <b>My Clients → Chat History</b>.
                    </div>
                `);
            return;
        }

        if (loadMessagesNow) loadMessages(true);
    });
}


/* ============================================================
   LOAD FULL MESSAGES
============================================================ */
function loadMessages(scrollBottom = false) {
    if (!currentTicketID) return;

    const chatBox = $("#chat-messages");

    chatBox.removeClass("is-ready").addClass("is-loading");

    $.post("/CSR/chat/load_messages.php", {
        ticket_id: currentTicketID,
        nocache: Date.now()
    }, html => {

        chatBox.html(html);

        const last = chatBox.find(".message").last();
        lastMessageID = last.length ? parseInt(last.data("msg-id"), 10) : 0;

        bindActionButtons();

        requestAnimationFrame(() => {
            chatBox.removeClass("is-loading").addClass("is-ready");
            if (scrollBottom) scrollToBottom();
        });
    });
}


/* ============================================================
   FETCH NEW MESSAGES
============================================================ */
function fetchNewMessages() {
    if (!currentTicketID) return;
    if ($("#ticket-status-dropdown").val() === "resolved") return;
    if ($("#chat-messages").hasClass("is-loading")) return;

    $.post("/CSR/chat/load_messages.php", {
        ticket_id: currentTicketID,
        nocache: Date.now()
    }, html => {

        const temp = $("<div>").html(html);
        let added = false;

        temp.find(".message").each(function () {
            const id = parseInt($(this).data("msg-id"), 10);
            if (id > lastMessageID) {
                $("#chat-messages").append($(this).css("opacity", 0));
                lastMessageID = id;
                added = true;
            }
        });

        if (added) {
            requestAnimationFrame(() => {
                $("#chat-messages .message").slice(-5).css("opacity", 1);
                scrollToBottom();
            });
        }

        bindActionButtons();
    });
}


/* ============================================================
   SEND MESSAGE / EDIT MESSAGE
============================================================ */
function sendMessage() {

    const msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID || !currentTicketID) return;
    if ($("#ticket-status-dropdown").val() === "resolved") return;

    if (editingMessageId) {
        updateMessage(editingMessageId);
        return;
    }

    appendTempBubble(msg);
    $("#chat-input").val("");

    $.post("/CSR/chat/send_message.php", {
        client_id: currentClientID,
        ticket_id: currentTicketID,
        message: msg,
        nocache: Date.now()
    });
}


/* ============================================================
   TEMP MESSAGE
============================================================ */
function appendTempBubble(msg) {

    $(".temp-msg").remove();

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
   CHAT PERMISSIONS
============================================================ */
function handleChatPermission(isAssignedToMe, isLocked, ticketStatus) {

    const input = $("#chat-input");
    const btn = $("#send-btn");
    const bar = $(".chat-input-area");

    if (!isAssignedToMe || isLocked || ticketStatus === "resolved") {
        bar.addClass("disabled");
        input.prop("disabled", true);
        btn.prop("disabled", true);
        return;
    }

    bar.removeClass("disabled");
    input.prop("disabled", false);
    btn.prop("disabled", false);
}


/* ============================================================
   SCROLL HANDLING
============================================================ */
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 120);
    $(".scroll-bottom-btn").removeClass("show");
}

function handleScrollButton() {
    const box = $("#chat-messages")[0];
    const nearBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 120;
    $(".scroll-bottom-btn").toggleClass("show", !nearBottom);
}


/* ============================================================
   TICKET STATUS CHANGE
============================================================ */
$(document).on("change", "#ticket-status-dropdown", function () {

    if (!currentTicketID || !currentClientID) return;

    const dropdown = $(this);
    const newStatus = dropdown.val();
    const oldStatus = dropdown.data("current");

    if (newStatus === oldStatus) return;

    dropdown
        .data("current", newStatus)
        .removeClass("unresolved pending resolved")
        .addClass(newStatus)
        .addClass("saving");

    animateTicketBadge(newStatus);

    $.post("/CSR/chat/ticket_update.php", {
        client_id: currentClientID,
        ticket_id: currentTicketID,
        status: newStatus,
        nocache: Date.now()
    }, "json")
    .done(res => {

        if (!res || res.ok !== true) {
            alert("Failed to update ticket status.");
            dropdown
                .removeClass("saving " + newStatus)
                .addClass(oldStatus)
                .val(oldStatus)
                .data("current", oldStatus);
            animateTicketBadge(oldStatus);
            return;
        }

        dropdown.removeClass("saving");
        loadClients(false);
        loadClientInfo(currentClientID, false);
    });
});


/* ============================================================
   BADGE ANIMATION
============================================================ */
function animateTicketBadge(status) {

    const badge = $(".client-item.active-client .ticket-badge");
    if (!badge.length) return;

    badge
        .removeClass("unresolved pending resolved status-updated")
        .addClass(status)
        .addClass("status-updated");

    setTimeout(() => badge.removeClass("status-updated"), 300);
}


/* ============================================================
   ACTION MENU — POSITIONED BESIDE BUBBLE
============================================================ */
function bindActionButtons() {
    $(".message-more-btn").off("click").on("click", function (e) {
        e.stopPropagation();

        const msg = $(this).closest(".message");
        const bubble = msg.find(".message-bubble");
        if (!bubble.length) return;

        openActionPopup(msg, bubble);
    });
}

function openActionPopup(messageEl, bubbleEl) {

    const popup = $("#msg-action-popup");
    const container = $("#chat-messages");

    const bubbleRect = bubbleEl[0].getBoundingClientRect();
    const containerRect = container[0].getBoundingClientRect();

    let top = bubbleRect.top - containerRect.top + container.scrollTop();
    let left;

    if (messageEl.hasClass("sent")) {
        left = bubbleRect.left - containerRect.left - popup.outerWidth() - 12;
    } else {
        left = bubbleRect.right - containerRect.left + 12;
    }

    if (left < 8) left = 8;

    popup
        .data("msg-id", messageEl.data("msg-id"))
        .css({ top, left })
        .show();
}

function closeActionPopup() {
    $("#msg-action-popup").hide();
}


/* ============================================================
   ACTION POPUP BUTTONS
============================================================ */
$(document).on("click", "#msg-action-popup button", function () {

    const action = $(this).data("action");
    const msgId = $("#msg-action-popup").data("msg-id");
    if (!action || !msgId) return;

    if (action === "delete") {
        if (!confirm("Delete this message?")) return;
        $.post("/CSR/chat/delete_message.php", { msg_id: msgId }, () => loadMessages(true));
    }

    if (action === "unsend") {
        if (!confirm("Unsend this message?")) return;
        $.post("/CSR/chat/unsend_message.php", { msg_id: msgId }, () => loadMessages(true));
    }

    if (action === "edit") {
        startEditMessage(msgId);
    }

    closeActionPopup();
});


/* ============================================================
   EDIT MESSAGE
============================================================ */
function startEditMessage(msgId) {

    const bubble = $(`.message[data-msg-id='${msgId}'] .message-bubble`);
    if (!bubble.length) return;

    editingMessageId = msgId;
    $("#chat-input").val(bubble.text().trim()).focus();
    $("#send-btn").text("Update");
}

function updateMessage(msgId) {

    const newText = $("#chat-input").val().trim();
    if (!newText) return;

    $.post("/CSR/chat/edit_message.php", {
        msg_id: msgId,
        message: newText
    }, () => {

        editingMessageId = null;
        $("#chat-input").val("");
        $("#send-btn").text("Send");
        loadMessages(true);
    });
}
