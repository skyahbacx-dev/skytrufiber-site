// ============================================================
// SkyTruFiber CSR Chat System — TICKET-BASED CHAT (FINAL VERSION)
// Each ticket has its own chat. No more mixing of old messages.
// ============================================================

let currentClientID = null;
let currentTicketID = null;   // 🔥 NEW — CRITICAL FIX FOR YOUR SYSTEM
let messageInterval = null;
let clientRefreshInterval = null;
let editing = false;

let currentTicketFilter = "all"; // all | unresolved | pending | resolved

$(document).ready(function () {

    // INITIAL LOAD --------------------------------------------
    loadClients();
    clientRefreshInterval = setInterval(loadClients, 4000);

    // SEARCH BAR ----------------------------------------------
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // FILTER BUTTONS ------------------------------------------
    $(document).on("click", ".ticket-filter", function () {
        currentTicketFilter = $(this).data("filter");
        $(".ticket-filter").removeClass("active");
        $(this).addClass("active");
        loadClients();
    });

    // SELECT CLIENT -------------------------------------------
    $(document).on("click", ".client-item", function (e) {

        if ($(e.target).closest(".assign-btn, .unassign-btn, .locked-icon").length)
            return;

        $(".client-item").removeClass("active-client");
        $(this).addClass("active-client");

        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));

        $("#chat-messages").html("");

        // Load CSR-side info INCLUDING TICKET ID
        loadClientInfo(currentClientID);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(fetchNewMessages, 1000);
    });

    // SEND MESSAGE --------------------------------------------
    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // SCROLL HANDLING -----------------------------------------
    $("#chat-messages").on("scroll", function () {
        const box = this;
        const dist = box.scrollHeight - box.clientHeight - box.scrollTop;
        $("#scroll-bottom-btn").toggleClass("show", dist > 80);
    });
    $("#scroll-bottom-btn").click(scrollToBottom);

    // CLOSE POPUP ---------------------------------------------
    $(document).on("click", e => {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) {
            closeActionPopup();
        }
    });

    // STATUS UPDATE -------------------------------------------
    $(document).on("change", "#ticket-status-dropdown", function () {

        if (!currentClientID || !currentTicketID) return;

        $.post("../chat/ticket_update.php", {
            client_id: currentClientID,
            ticket_id: currentTicketID,
            status: $(this).val()
        }, (res) => {

            if (res === "OK") {
                loadClientInfo(currentClientID);
                loadMessages(true);
                loadClients();
            } else alert("Failed to update ticket.");
        });
    });

    // ASSIGN / UNASSIGN / TRANSFER ----------------------------
    $(document).on("click", ".assign-btn", function () {
        $.post("../chat/assign_client.php", {
            action: "assign",
            client_id: $(this).data("id")
        }, handleAssignResponse);
    });

    $(document).on("click", ".unassign-btn", function () {
        const id = $(this).data("id");
        $.post("../chat/assign_client.php", {
            action: "unassign",
            client_id: id
        }, res => {
            alert(res.msg);
            loadClientInfo(id);
            loadClients();
        });
    });

    $(document).on("click", ".request-transfer-btn", function () {
        $.post("../chat/assign_client.php", {
            action: "request_transfer",
            client_id: $(this).data("id")
        }, res => {
            alert(res.msg);
            loadClientInfo(currentClientID);
            loadClients();
        });
    });

    $(document).on("click", ".approve-transfer-btn", function () {
        $.post("../chat/assign_client.php", {
            action: "approve_transfer",
            client_id: $(this).data("id")
        }, res => {
            alert(res.msg);
            loadClientInfo(currentClientID);
            loadClients();
        });
    });

    $(document).on("click", ".deny-transfer-btn", function () {
        $.post("../chat/assign_client.php", {
            action: "deny_transfer",
            client_id: $(this).data("id")
        }, res => {
            alert(res.msg);
            loadClientInfo(currentClientID);
            loadClients();
        });
    });
});

// ============================================================
// ASSIGN RESPONSE HANDLER
// ============================================================
function handleAssignResponse(res) {

    if (res.status === "ok") {
        alert(res.msg);
        loadClientInfo(currentClientID);
        loadClients();
        return;
    }

    if (res.status === "transfer_required") {

        if (confirm(`${res.msg}\nAssigned to: ${res.assigned_to}\nRequest transfer?`)) {

            $.post("../chat/assign_client.php", {
                action: "request_transfer",
                client_id: currentClientID
            }, out => {
                alert(out.msg);
                loadClientInfo(currentClientID);
                loadClients();
            });
        }
        return;
    }

    alert(res.msg);
}

// ============================================================
// SCROLL
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
        if (currentClientID)
            $(`.client-item[data-id='${currentClientID}']`).addClass("active-client");
    });
}

// ============================================================
// LOAD CLIENT INFO + CRITICAL: GET TICKET ID
// ============================================================
function loadClientInfo(id) {

    $.post("../chat/load_client_info.php", { client_id: id }, html => {

        $("#client-info-content").html(html);

        const meta = $("#client-meta");
        if (!meta.length) return;

        // 🔥 IMPORTANT: GET CURRENT ACTIVE TICKET
        currentTicketID = parseInt(meta.data("ticket-id")) || null;

        const ticketStatus = meta.data("ticket");
        const isAssignedToMe = String(meta.data("assigned")) === "1";
        const isLocked = String(meta.data("locked")) === "true";

        $("#ticket-status-dropdown").val(ticketStatus);
        $("#ticket-status-dropdown").prop("disabled", !isAssignedToMe);

        handleChatPermission(isAssignedToMe, isLocked, ticketStatus);

        // Now load chat using TICKET ID, NOT CLIENT ID
        loadMessages(true);
    });
}

// ============================================================
// PERMISSIONS
// ============================================================
function handleChatPermission(isAssignedToMe, isLocked, ticketStatus) {

    const bar = $(".chat-input-area");
    const input = $("#chat-input");
    const sendBtn = $("#send-btn");

    if (!isAssignedToMe || isLocked) {
        bar.addClass("disabled");
        input.prop("disabled", true);
        sendBtn.prop("disabled", true);
        input.attr("placeholder",
            isLocked ? "🔒 Ticket locked." : "Assigned to another CSR — view only."
        );
        return;
    }

    if (ticketStatus === "resolved") {
        bar.addClass("disabled");
        input.prop("disabled", true);
        sendBtn.prop("disabled", true);
        input.attr("placeholder", "Ticket resolved — chat closed.");
        return;
    }

    bar.removeClass("disabled");
    input.prop("disabled", false);
    sendBtn.prop("disabled", false);
    input.attr("placeholder", "Type a message...");
}

// ============================================================
// LOAD MESSAGES (NOW USING TICKET ID)
// ============================================================
function loadMessages(scrollBottom = false) {

    if (!currentTicketID) return;

    $.post("../chat/load_messages.php", {
        ticket_id: currentTicketID   // 🔥 FIXED
    }, html => {

        $("#chat-messages").html(html);
        bindActionButtons();

        if (scrollBottom) scrollToBottom();
    });
}

// ============================================================
// FETCH NEW MESSAGES
// ============================================================
function fetchNewMessages() {

    if (!currentTicketID) return;

    $.post("../chat/load_messages.php", {
        ticket_id: currentTicketID   // 🔥 FIXED
    }, html => {

        const temp = $("<div>").html(html);

        temp.find(".message").each(function () {
            const id = $(this).data("msg-id");
            if (!$(`.message[data-msg-id='${id}']`).length) {
                $("#chat-messages").append($(this));
            }
        });

        bindActionButtons();
    });
}

// ============================================================
// SEND MESSAGE (NOW INCLUDES TICKET ID)
// ============================================================
function sendMessage() {

    const msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID || !currentTicketID) return;

    appendTempBubble(msg);

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        ticket_id: currentTicketID,   // 🔥 CRITICAL FIX
        message: msg
    }, (res) => {

        if (res.status === "blocked" || res.status === "locked") {
            alert(res.msg);
            loadClientInfo(currentClientID);
            loadMessages(true);
            return;
        }

        $("#chat-input").val("");
        setTimeout(fetchNewMessages, 200);
    });
}

// ============================================================
// TEMPORARY SEND BUBBLE
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
    popup.data("msg-id", id);

    const bubble = $(anchor).closest(".message-content");
    const pos = bubble.offset();
    const wrapper = $(".chat-wrapper").offset();

    let top = pos.top - wrapper.top - popup.outerHeight() - 10;
    let left = pos.left - wrapper.left + bubble.outerWidth() - popup.outerWidth();

    if (top < 10) top = 10;

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
        <textarea class='edit-textarea'>${oldText}</textarea>
        <div class='edit-actions'>
            <button class='edit-save' data-id='${id}'>Save</button>
            <button class='edit-cancel'>Cancel</button>
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

// ============================================================
// DELETE MESSAGE
// ============================================================
$(document).on("click", ".action-delete, .action-unsend", function () {

    const id = $("#msg-action-popup").data("msg-id");

    $.post("../chat/delete_message.php", { id }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});
