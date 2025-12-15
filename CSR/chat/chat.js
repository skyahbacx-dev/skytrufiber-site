// ============================================================
// SkyTruFiber CSR Chat System — STABLE & DUPLICATE-FREE VERSION
// ============================================================

let currentClientID = null;
let currentTicketID = null;
let lastMessageID = 0;
let messageInterval = null;
let clientListInterval = null;
let clientInfoInterval = null;
let editing = false;

let currentTicketFilter = "all";

$(document).ready(function () {

    // INITIAL LOAD
    loadClients();

    // CLIENT LIST AUTO REFRESH
    clientListInterval = setInterval(() => {
        loadClients(false);
    }, 6000);

    // CLIENT INFO AUTO REFRESH (NO MESSAGE RESET)
    clientInfoInterval = setInterval(() => {
        if (currentClientID) {
            loadClientInfo(currentClientID, false);
        }
    }, 4000);

    // SEARCH
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // FILTER
    $(document).on("click", ".ticket-filter", function () {
        currentTicketFilter = $(this).data("filter");
        $(".ticket-filter").removeClass("active");
        $(this).addClass("active");
        loadClients();
    });

    // SELECT CLIENT
    $(document).on("click", ".client-item", function (e) {

        if ($(e.target).closest(".assign-btn,.unassign-btn,.locked-icon").length) return;

        $(".client-item").removeClass("active-client");
        $(this).addClass("active-client");

        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));

        lastMessageID = 0;
        $("#chat-messages").html("");

        loadClientInfo(currentClientID, true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(fetchNewMessages, 1000);
    });

    // SEND MESSAGE
    $("#send-btn").on("click", sendMessage);
    $("#chat-input").on("keypress", e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
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
// LOAD CLIENT INFO (NEVER TOUCH MESSAGES UNLESS FORCED)
// ============================================================
function loadClientInfo(id, reloadMessages = false) {

    $.post("/CSR/chat/load_client_info.php", {
        client_id: id,
        nocache: Date.now()
    }, html => {

        $("#client-info-content").html(html);

        const meta = $("#client-meta");
        if (!meta.length) return;

        currentTicketID = parseInt(meta.data("ticket-id")) || null;

        const assigned = meta.data("assigned") === "yes";
        const locked = meta.data("locked") === "true";
        const status = meta.data("ticket");

        $("#ticket-status-dropdown")
            .val(status)
            .prop("disabled", !assigned);

        handleChatPermission(assigned, locked, status);

        if (reloadMessages) {
            loadMessages(true);
        }
    });
}

// ============================================================
// LOAD FULL MESSAGE LIST (ONLY ON CLIENT SELECT)
// ============================================================
function loadMessages(scrollBottom = false) {
    if (!currentTicketID) return;

    $.post("/CSR/chat/load_messages.php", {
        ticket_id: currentTicketID,
        nocache: Date.now()
    }, html => {

        $("#chat-messages").html(html);

        const last = $("#last-msg-id").data("last-id");
        lastMessageID = last ? parseInt(last) : 0;

        bindActionButtons();
        if (scrollBottom) scrollToBottom();
    });
}

// ============================================================
// FETCH ONLY NEW MESSAGES (NO REPLACE, NO FLICKER)
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
                $(".temp-msg").remove(); // 🔥 remove temp
                $("#chat-messages").append($(this));
                lastMessageID = id;
                scrollToBottom();
            }
        });

        bindActionButtons();
    });
}

// ============================================================
// SEND MESSAGE (TEMP REMOVED SAFELY)
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
    }, res => {

        if (res.status === "blocked" || res.status === "locked") {
            alert(res.msg);
            $(".temp-msg").remove();
            loadClientInfo(currentClientID, true);
        }
    });
}

// ============================================================
// TEMP MESSAGE
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
// PERMISSIONS
// ============================================================
function handleChatPermission(assigned, locked, status) {

    const input = $("#chat-input");
    const btn = $("#send-btn");
    const bar = $(".chat-input-area");

    if (!assigned || locked || status === "resolved") {
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
// SCROLL
// ============================================================
function scrollToBottom() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 150);
}

// ============================================================
// ACTION MENU
// ============================================================
function bindActionButtons() {
    $(".more-btn").off("click").on("click", function (e) {
        e.stopPropagation();
        openActionPopup($(this).data("id"), this);
    });
}

function openActionPopup(id, anchor) {
    const popup = $("#msg-action-popup");
    popup.data("msg-id", id).show();
}

function closeActionPopup() {
    $("#msg-action-popup").hide();
}
