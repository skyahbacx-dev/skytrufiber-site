// ============================================================
// SkyTruFiber CSR Chat System — FULL ASSIGNMENT + LOCK + PENDING BUILD
// ============================================================

let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let lastMessageID = 0;
let editing = false;

let currentTicketFilter = "all"; // all | unresolved | resolved | pending

$(document).ready(function () {

    // Load clients always
    loadClients();
    clientRefreshInterval = setInterval(loadClients, 4000);

    // ------------------------------------------------------------
    // SEARCH CLIENT
    // ------------------------------------------------------------
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // ------------------------------------------------------------
    // TICKET FILTER BUTTONS
    // ------------------------------------------------------------
    $(document).on("click", ".ticket-filter", function () {
        currentTicketFilter = $(this).data("filter");
        $(".ticket-filter").removeClass("active");
        $(this).addClass("active");
        loadClients();
    });

    // ------------------------------------------------------------
    // SEND MESSAGE
    // ------------------------------------------------------------
    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // ------------------------------------------------------------
    // SELECT A CLIENT
    // ------------------------------------------------------------
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
        messageInterval = setInterval(fetchNewMessages, 1200);
    });

    // ------------------------------------------------------------
    // SCROLL BUTTON
    // ------------------------------------------------------------
    $("#chat-messages").on("scroll", function () {
        const box = this;
        const dist = box.scrollHeight - box.clientHeight - box.scrollTop;
        $("#scroll-bottom-btn").toggleClass("show", dist > 80);
    });

    $("#scroll-bottom-btn").click(scrollToBottom);

    // ------------------------------------------------------------
    // CLOSE ACTION POPUP
    // ------------------------------------------------------------
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) {
            closeActionPopup();
        }
    });

    // ------------------------------------------------------------
    // CHANGE TICKET STATUS (unresolved, resolved, pending)
    // ------------------------------------------------------------
    $(document).on("change", "#ticket-status-dropdown", function () {

        if (!currentClientID) return;

        const newStatus = $(this).val();

        $.post("../chat/ticket_update.php", {
            client_id: currentClientID,
            status: newStatus
        }, function (res) {
            if (res === "OK") {
                loadClientInfo(currentClientID);
                loadClients();
            }
        });
    });

});

// ============================================================
// SCROLL TO BOTTOM
// ============================================================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 200);
}

// ============================================================
// LOAD CLIENT LIST
// ============================================================
function loadClients() {
    $.post("../chat/load_clients.php", { filter: currentTicketFilter }, html => {
        $("#client-list").html(html);

        if (currentClientID) {
            $(`.client-item[data-id='${currentClientID}']`).addClass("active-client");
        }
    });
}

// ============================================================
// LOAD CLIENT INFO PANEL
// Shows:
//   ✔ Assigned CSR
//   ✔ Lock status
//   ✔ Pending
//   ✔ Banner message
// ============================================================
function loadClientInfo(id) {

    $.post("../chat/load_client_info.php", { client_id: id }, html => {

        $("#client-info-content").html(html);

        const meta = $("#client-meta");

        if (meta.length) {

            const ticketStatus   = meta.data("ticket");
            const assignedCSR    = meta.data("assigned-csr");
            const myCSR          = meta.data("me");
            const isLocked       = meta.data("locked") == "1";
            const transferReq    = meta.data("transfer-request");

            const isAssignedToMe = (assignedCSR === myCSR);

            updateBanner(assignedCSR, myCSR, transferReq);

            // Chat-panel border color
            $("#ticket-border-panel")
                .removeClass("resolved pending unresolved")
                .addClass(ticketStatus);

            // Enable or disable dropdown
            $("#ticket-status-dropdown").prop("disabled", !isAssignedToMe);

            // Input permission
            handleChatPermission(isAssignedToMe, isLocked, ticketStatus);
        }
    });
}

// ============================================================
// HEADER BANNER LOGIC
// ============================================================
function updateBanner(assignedCSR, myCSR, transferReq) {

    const banner = $("#chat-header-banner");
    if (!banner.length) return;

    // Reset
    banner.hide().text("");

    // Not assigned at all
    if (!assignedCSR) {
        banner.text("⚠ This client is unassigned — Click 🔒 to assign.").show();
        return;
    }

    // Assigned to me
    if (assignedCSR === myCSR) {
        banner.text("✔ You are assigned to this client.").show();
        return;
    }

    // Assigned to another CSR
    banner.text(`🔒 Assigned to ${assignedCSR}. You cannot reply.`).show();
}

// ============================================================
// CHAT PERMISSIONS
// ============================================================
function handleChatPermission(isAssignedToMe, isLocked, ticketStatus) {

    const bar = $(".chat-input-area");
    const input = $("#chat-input");
    const sendBtn = $("#send-btn");

    // Not assigned OR locked
    if (!isAssignedToMe || isLocked) {

        bar.addClass("disabled");
        input.prop("disabled", true);
        sendBtn.prop("disabled", true);

        input.attr("placeholder",
            isLocked
                ? "Client is locked — cannot send."
                : "Assigned to another CSR — cannot send."
        );
        return;
    }

    // Ticket resolved
    if (ticketStatus === "resolved") {
        bar.addClass("disabled");
        input.prop("disabled", true);
        sendBtn.prop("disabled", true);
        input.attr("placeholder", "Ticket is resolved — chat closed.");
        return;
    }

    // Pending (CSR + Technician)
    if (ticketStatus === "pending") {
        bar.removeClass("disabled");
        input.prop("disabled", false);
        sendBtn.prop("disabled", false);
        input.attr("placeholder", "Technician is coordinating — you may update the client.");
        return;
    }

    // Full access
    bar.removeClass("disabled");
    input.prop("disabled", false);
    sendBtn.prop("disabled", false);
    input.attr("placeholder", "Type a message...");
}

// ============================================================
// LOAD ALL MESSAGES
// ============================================================
function loadMessages(scrollBottom = false) {

    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, html => {

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
// FETCH ONLY NEW MESSAGES
// ============================================================
function fetchNewMessages() {

    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, html => {

        const temp = $("<div>").html(html);
        const incoming = temp.find(".message");

        incoming.each(function () {
            const id = $(this).data("msg-id");
            if (!$(`.message[data-msg-id='${id}']`).length) {
                $("#chat-messages").append($(this));
            }
        });

        bindActionButtons();

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
    if (!msg || !currentClientID) return;

    appendTempBubble(msg);

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg
    }).done(() => {
        $("#chat-input").val("");
        setTimeout(fetchNewMessages, 200);
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
        </div>
    `);
    scrollToBottom();
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

    popup.css({ top, left }).show().addClass("show");
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
        </div>
    `);
}

$(document).on("click", ".edit-save", function () {

    const id = $(this).data("id");
    const newText = $(this)
        .closest(".message-bubble")
        .find("textarea")
        .val()
        .trim();

    $.post("../chat/edit_message.php", { id, message: newText }, function () {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", function () {
    editing = false;
    loadMessages(false);
});

// ============================================================
// DELETE / UNSEND
// ============================================================
$(document).on("click", ".action-delete, .action-unsend", function () {

    const id = $("#msg-action-popup").data("msg-id");

    $.post("../chat/delete_message.php", { id }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});
