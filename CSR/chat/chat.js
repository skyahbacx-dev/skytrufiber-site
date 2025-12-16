// ============================================================
// SkyTruFiber CSR Chat System — STABLE, FIXED VERSION
// ============================================================

let currentClientID = null;
let currentTicketID = null;
let lastMessageID = 0;

let currentTicketFilter = "all";

let clientListInterval = null;
let messageInterval = null;
let clientInfoInterval = null;

$(document).ready(function () {

    // INITIAL LOAD
    loadClients();

    // CLIENT LIST REFRESH
    clientListInterval = setInterval(() => {
        loadClients(false);
    }, 6000);

    // CLIENT INFO REFRESH (NO MESSAGE RELOAD)
    clientInfoInterval = setInterval(() => {
        if (currentClientID) loadClientInfo(currentClientID, false);
    }, 3000);

    // SEARCH
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // FILTERS
    $(document).on("click", ".ticket-filter", function () {
        currentTicketFilter = $(this).data("filter");
        $(".ticket-filter").removeClass("active");
        $(this).addClass("active");
        loadClients();
    });

    // SELECT CLIENT
    $(document).on("click", ".client-item", function (e) {

        if ($(e.target).closest(".assign-btn, .unassign-btn, .locked-icon").length) return;

        $(".client-item").removeClass("active-client");
        $(this).addClass("active-client");

        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));

        $("#chat-messages").html("");
        lastMessageID = 0;

        loadClientInfo(currentClientID, true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(fetchNewMessages, 1000);
    });

    // SEND MESSAGE
    $("#send-btn").on("click", sendMessage);
    $("#chat-input").on("keypress", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // SCROLL HANDLING
    $("#chat-messages").on("scroll", handleScrollButton);
    $(document).on("click", ".scroll-bottom-btn", scrollToBottom);

    // CLOSE ACTION MENU WHEN CLICKING OUTSIDE
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .message-more-btn").length) {
            closeActionPopup();
        }
    });

    // PREVENT POPUP FROM CLOSING WHEN CLICKED
    $("#msg-action-popup").on("click", function (e) {
        e.stopPropagation();
    });
});


// ============================================================
// LOAD CLIENT LIST
// ============================================================
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


// ============================================================
// LOAD CLIENT INFO
// ============================================================
function loadClientInfo(id, loadMessagesNow = false) {

    $.post("/CSR/chat/load_client_info.php", {
        client_id: id,
        nocache: Date.now()
    }, html => {

        $("#client-info-content").html(html);

        const meta = $("#client-meta");
        if (!meta.length) return;

        currentTicketID = parseInt(meta.data("ticket-id")) || null;

        const assignedToMe = meta.data("assigned") === "yes";
        const locked = meta.data("locked") === "true";
        const ticketStatus = meta.data("ticket"); // unresolved | resolved | pending

        const dropdown = $("#ticket-status-dropdown");

        dropdown
            .val(ticketStatus)
            .prop("disabled", !assignedToMe)
            .removeClass("unresolved resolved pending")
            .addClass(ticketStatus);

        handleChatPermission(assignedToMe, locked, ticketStatus);

        if (loadMessagesNow) loadMessages(true);
    });
}


// ============================================================
// LOAD FULL MESSAGES
// ============================================================
function loadMessages(scrollBottom = false) {
    if (!currentTicketID) return;

    $.post("/CSR/chat/load_messages.php", {
        ticket_id: currentTicketID,
        nocache: Date.now()
    }, html => {

        $("#chat-messages").html(html);

        const last = $("#chat-messages .message").last();
        lastMessageID = last.length ? parseInt(last.data("msg-id")) : 0;

        bindActionButtons();
        if (scrollBottom) scrollToBottom();
    });
}


// ============================================================
// FETCH NEW MESSAGES (APPEND ONLY)
// ============================================================
function fetchNewMessages() {
    if (!currentTicketID) return;

    $.post("/CSR/chat/load_messages.php", {
        ticket_id: currentTicketID,
        nocache: Date.now()
    }, html => {

        const temp = $("<div>").html(html);

        temp.find(".message").each(function () {
            const id = parseInt($(this).data("msg-id"));

            if (id > lastMessageID) {
                $(".temp-msg").remove();
                $("#chat-messages").append($(this));
                lastMessageID = id;
                scrollToBottom();
            }
        });

        bindActionButtons();
    });
}


// ============================================================
// SEND MESSAGE
// ============================================================
function sendMessage() {

    const msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID || !currentTicketID) return;

    appendTempBubble(msg);
    $("#chat-input").val("");

    $.post("/CSR/chat/send_message.php", {
        client_id: currentClientID,
        ticket_id: currentTicketID,
        message: msg,
        nocache: Date.now()
    });
}


// ============================================================
// TEMP MESSAGE BUBBLE
// ============================================================
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


// ============================================================
// CHAT PERMISSIONS
// ============================================================
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


// ============================================================
// SCROLL HANDLING
// ============================================================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 120);
    $(".scroll-bottom-btn").removeClass("show");
}

function handleScrollButton() {
    const box = $("#chat-messages")[0];
    const nearBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 120;

    if (nearBottom) {
        $(".scroll-bottom-btn").removeClass("show");
    } else {
        $(".scroll-bottom-btn").addClass("show");
    }
}


// ============================================================
// ACTION MENU (⋮) — FIXED
// ============================================================
function bindActionButtons() {
    $(".message-more-btn").off("click").on("click", function (e) {
        e.stopPropagation();

        const id = $(this).data("id");
        const rect = this.getBoundingClientRect();

        openActionPopup(id, rect);
    });
}

function openActionPopup(id, rect) {
    const popup = $("#msg-action-popup");

    popup
        .data("msg-id", id)
        .css({
            top: rect.bottom + window.scrollY + 6,
            left: rect.left + window.scrollX - 120
        })
        .show();
}

function closeActionPopup() {
    $("#msg-action-popup").hide();
}
