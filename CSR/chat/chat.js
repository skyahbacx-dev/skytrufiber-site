// ============================================================
// SkyTruFiber CSR Chat System — FINAL FLICKER-FREE VERSION
// ============================================================

let currentClientID = null;
let currentTicketID = null;
let lastMessageID = 0;       // ⭐ NEW → Track last message to avoid flicker
let editing = false;

let currentTicketFilter = "all";

let clientListInterval = null;
let messageInterval = null;
let clientInfoInterval = null;

$(document).ready(function () {

    // INITIAL LOAD
    loadClients();

    // AUTO-REFRESH CLIENT LIST (Left Panel)
    clientListInterval = setInterval(() => {
        loadClients(false);
    }, 6000);

    // AUTO-REFRESH CLIENT INFO (Right Panel)
    clientInfoInterval = setInterval(() => {
        if (currentClientID) loadClientInfo(currentClientID, false);
    }, 3000);

    // SEARCH FILTER
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // TICKET FILTER BUTTONS
    $(document).on("click", ".ticket-filter", function () {
        currentTicketFilter = $(this).data("filter");
        $(".ticket-filter").removeClass("active");
        $(this).addClass("active");
        loadClients();
    });

    // SELECT CLIENT
    $(document).on("click", ".client-item", function (e) {

        if ($(e.target).closest(".assign-btn, .unassign-btn, .locked-icon").length)
            return;

        $(".client-item").removeClass("active-client");
        $(this).addClass("active-client");

        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));
        $("#chat-messages").html("");

        lastMessageID = 0; // RESET message tracking

        loadClientInfo(currentClientID, true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(fetchNewMessages, 900);
    });

    // SEND MESSAGE
    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // SCROLL BUTTON CONTROL
    $("#chat-messages").on("scroll", function () {
        const box = this;
        const dist = box.scrollHeight - box.clientHeight - box.scrollTop;
        $("#scroll-bottom-btn").toggleClass("show", dist > 80);
    });
    $("#scroll-bottom-btn").click(scrollToBottom);

    // CLOSE ACTION MENU
    $(document).on("click", e => {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) {
            closeActionPopup();
        }
    });

    // TICKET STATUS CHANGE
    $(document).on("change", "#ticket-status-dropdown", function () {

        if (!currentClientID || !currentTicketID) return;

        $.post("/CSR/chat/ticket_update.php", {
            client_id: currentClientID,
            ticket_id: currentTicketID,
            status: $(this).val(),
            nocache: Date.now()
        }, raw => {

            const res = typeof raw === "string" ? JSON.parse(raw) : raw;

            if (!res.ok) {
                alert("Failed: " + res.msg);
                return;
            }

            setTimeout(() => {
                loadClientInfo(currentClientID, true);
                loadClients(false);
            }, 200);
        });
    });

    // ASSIGN / TRANSFER HANDLERS --------------------------------

    $(document).on("click", ".assign-btn", function () {
        $.post("/CSR/chat/assign_client.php", {
            action: "assign",
            client_id: $(this).data("id")
        }, handleAssignResponse);
    });

    $(document).on("click", ".unassign-btn", function () {
        const id = $(this).data("id");

        $.post("/CSR/chat/assign_client.php", {
            action: "unassign",
            client_id: id
        }, res => {
            alert(res.msg);
            loadClientInfo(id, true);
            loadClients(false);
        });
    });

    $(document).on("click", ".request-transfer-btn", function () {
        $.post("/CSR/chat/assign_client.php", {
            action: "request_transfer",
            client_id: $(this).data("id")
        }, res => {
            alert(res.msg);
            loadClientInfo(currentClientID, true);
            loadClients(false);
        });
    });

    $(document).on("click", ".approve-transfer-btn", function () {
        $.post("/CSR/chat/assign_client.php", {
            action: "approve_transfer",
            client_id: $(this).data("id")
        }, res => {
            alert(res.msg);
            loadClientInfo(currentClientID, true);
            loadClients(false);
        });
    });

    $(document).on("click", ".deny-transfer-btn", function () {
        $.post("/CSR/chat/assign_client.php", {
            action: "deny_transfer",
            client_id: $(this).data("id")
        }, res => {
            alert(res.msg);
            loadClientInfo(currentClientID, true);
            loadClients(false);
        });
    });
});


// ============================================================
// ASSIGN RESPONSE
// ============================================================
function handleAssignResponse(res) {

    if (res.status === "ok") {
        alert(res.msg);
        loadClientInfo(currentClientID, true);
        loadClients(false);
        return;
    }

    if (res.status === "transfer_required") {
        if (confirm(`${res.msg}\n\nAssigned to: ${res.assigned_to}\nRequest transfer?`)) {
            $.post("/CSR/chat/assign_client.php", {
                action: "request_transfer",
                client_id: currentClientID
            }, out => {
                alert(out.msg);
                loadClientInfo(currentClientID, true);
                loadClients(false);
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
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 150);
}


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
function loadClientInfo(id, refreshMessages = true) {

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
        const ticketStatus = meta.data("ticket");

        $("#ticket-status-dropdown").val(ticketStatus).prop("disabled", !assignedToMe);

        handleChatPermission(assignedToMe, locked, ticketStatus);

        if (refreshMessages) {
            lastMessageID = 0; // Reset
            loadMessages(true);
        }
    });
}


// ============================================================
// PERMISSIONS
// ============================================================
function handleChatPermission(isAssignedToMe, isLocked, ticketStatus) {

    const input = $("#chat-input");
    const btn = $("#send-btn");
    const bar = $(".chat-input-area");

    if (!isAssignedToMe || isLocked) {
        bar.addClass("disabled");
        input.prop("disabled", true).attr("placeholder",
            isLocked ? "🔒 Ticket locked." : "Assigned to another CSR — view only."
        );
        btn.prop("disabled", true);
        return;
    }

    if (ticketStatus === "resolved") {
        bar.addClass("disabled");
        input.prop("disabled", true).attr("placeholder", "Ticket resolved — chat closed.");
        btn.prop("disabled", true);
        return;
    }

    bar.removeClass("disabled");
    input.prop("disabled", false).attr("placeholder", "Type a message...");
    btn.prop("disabled", false);
}


// ============================================================
// LOAD FULL MESSAGE LIST (ONLY WHEN CLIENT SELECTED)
// ============================================================
function loadMessages(scrollBottom = false) {
    if (!currentTicketID) return;

    $.post("/CSR/chat/load_messages.php", {
        ticket_id: currentTicketID,
        nocache: Date.now()
    }, html => {

        // Reset messages completely
        $("#chat-messages").html(html);

        // Read the last real message ID from hidden <div>
        const lastID = $("#last-msg-id").data("last-id");
        lastMessageID = lastID ? parseInt(lastID) : 0;

        bindActionButtons();

        if (scrollBottom) scrollToBottom();
    });
}




// ============================================================
// FLICKER-FREE AUTO FETCH (ONLY APPEND NEW MESSAGES)
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

            // Only append NEW messages
            if (id > lastMessageID) {
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

    $.post("/CSR/chat/send_message.php", {
        client_id: currentClientID,
        ticket_id: currentTicketID,
        message: msg,
        nocache: Date.now()
    }, res => {

        if (res.status === "blocked" || res.status === "locked") {
            alert(res.msg);
            loadClientInfo(currentClientID, true);
            return;
        }

        $("#chat-input").val("");

        setTimeout(fetchNewMessages, 150);
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
// ACTION MENU + EDIT / DELETE
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
    const wrap = $(".chat-wrapper").offset();

    let top = pos.top - wrap.top - popup.outerHeight() - 10;
    let left = pos.left - wrap.left + bubble.outerWidth() - popup.outerWidth();

    if (top < 10) top = 10;

    popup.css({ top, left }).show().addClass("show");
}

function closeActionPopup() {
    $("#msg-action-popup").removeClass("show").hide();
}


// EDIT MESSAGE
$(document).on("click", ".action-edit", function () {
    const id = $("#msg-action-popup").data("msg-id");
    startCSRMessageEdit(id);
    closeActionPopup();
});

function startCSRMessageEdit(id) {
    editing = true;

    const bubble = $(`.message[data-msg-id='${id}'] .message-bubble`);
    const old = bubble.text().trim();

    bubble.html(`
        <textarea class='edit-textarea'>${old}</textarea>
        <div class='edit-actions'>
            <button class='edit-save' data-id='${id}'>Save</button>
            <button class='edit-cancel'>Cancel</button>
        </div>
    `);
}

$(document).on("click", ".edit-save", function () {
    const id = $(this).data("id");
    const text = $(this).closest(".message-bubble").find("textarea").val().trim();

    $.post("/CSR/chat/edit_message.php", {
        id,
        message: text,
        nocache: Date.now()
    }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", () => {
    editing = false;
    loadMessages(false);
});


// DELETE MESSAGE
$(document).on("click", ".action-delete, .action-unsend", function () {

    const id = $("#msg-action-popup").data("msg-id");

    $.post("/CSR/chat/delete_message.php", {
        id,
        nocache: Date.now()
    }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});
