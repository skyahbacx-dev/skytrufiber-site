// ============================================================
// SkyTruFiber CSR Chat System — FINAL UPDATED VERSION
// Uses CLIENT-ID based chat (matching your load_clients.php)
// ============================================================

let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let lastMessageID = 0;
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

        // Prevent conflict with assign/unassign buttons
        if ($(e.target).closest(".assign-btn, .unassign-btn, .locked-icon").length) return;

        $(".client-item").removeClass("active-client");
        $(this).addClass("active-client");

        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));

        $("#chat-messages").html("");
        lastMessageID = 0;

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(fetchNewMessages, 1000);
    });

    // SEND MESSAGE --------------------------------------------
    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // SCROLL BUTTON --------------------------------------------
    $("#chat-messages").on("scroll", function () {
        const box = this;
        const dist = box.scrollHeight - box.clientHeight - box.scrollTop;
        $("#scroll-bottom-btn").toggleClass("show", dist > 80);
    });

    $("#scroll-bottom-btn").click(scrollToBottom);

    // CLOSE ACTION MENU ----------------------------------------
    $(document).on("click", function (e) {
        if (!$(e.target).closest("#msg-action-popup, .more-btn").length) {
            closeActionPopup();
        }
    });

    // TICKET STATUS CHANGES -----------------------------------
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
            } else {
                alert("Failed to update ticket.");
            }
        });
    });

    // ASSIGN / UNASSIGN ---------------------------------------
    $(document).on("click", ".assign-btn", function () {
        const id = $(this).data("id");
        $.post("../chat/assign_client.php", {
            action: "assign",
            client_id: id
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

    // TRANSFER REQUESTS ---------------------------------------
    $(document).on("click", ".request-transfer-btn", function () {
        const id = $(this).data("id");
        $.post("../chat/assign_client.php", {
            action: "request_transfer",
            client_id: id
        }, res => {
            alert(res.msg);
            loadClientInfo(id);
            loadClients();
        });
    });

    $(document).on("click", ".approve-transfer-btn", function () {
        const id = $(this).data("id");
        $.post("../chat/assign_client.php", {
            action: "approve_transfer",
            client_id: id
        }, res => {
            alert(res.msg);
            loadClientInfo(id);
            loadClients();
        });
    });

    $(document).on("click", ".deny-transfer-btn", function () {
        const id = $(this).data("id");
        $.post("../chat/assign_client.php", {
            action: "deny_transfer",
            client_id: id
        }, res => {
            alert(res.msg);
            loadClientInfo(id);
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
// UI — SCROLL TO BOTTOM
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
// LOAD CLIENT INFO (permissions + status)
// ============================================================
function loadClientInfo(id) {

    $.post("../chat/load_client_info.php", { client_id: id }, html => {

        $("#client-info-content").html(html);

        const meta = $("#client-meta");
        if (!meta.length) return;

        const ticketStatus = meta.data("ticket");
        const isAssignedToMe = String(meta.data("assigned")) === "1";
        const isLocked = String(meta.data("locked")) === "true";

        $("#ticket-status-dropdown").val(ticketStatus);
        $("#ticket-status-dropdown").prop("disabled", !isAssignedToMe);

        handleChatPermission(isAssignedToMe, isLocked, ticketStatus);
    });
}

// ============================================================
// CHAT PERMISSIONS (input enable/disable)
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
            isLocked ? "🔒 Ticket locked." :
                       "Assigned to another CSR — view only."
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
// LOAD ALL MESSAGES
// ============================================================
function loadMessages(scrollBottom = false) {

    if (!currentClientID) return;

    $.post("../chat/load_messages.php", {
        client_id: currentClientID
    }, html => {

        $("#chat-messages").html(html);

        bindActionButtons();

        if (scrollBottom) scrollToBottom();
    });
}

// ============================================================
// FETCH ONLY NEW MESSAGES
// ============================================================
function fetchNewMessages() {

    if (!currentClientID) return;

    $.post("../chat/load_messages.php", {
        client_id: currentClientID
    }, html => {

        const temp = $("<div>").html(html);
        const incoming = temp.find(".message");

        incoming.each(function () {
            const id = $(this).data("msg-id");
            if (!$(`.message[data-msg-id='${id}']`).length) {
                $("#chat-messages").append($(this));
            }
        });

        bindActionButtons();
    });
}

// ============================================================
// SEND MESSAGE (CSR)
// ============================================================
function sendMessage() {

    const msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID) return;

    appendTempBubble(msg);

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg
    }).done(res => {

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
// TEMPORARY SENDING BUBBLE
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
// ACTION POPUP (Edit/Delete/Unsend)
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

// EDIT MESSAGE ---------------------------------------------
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
    const newText =
        $(this).closest(".message-bubble").find("textarea").val().trim();

    $.post("../chat/edit_message.php", { id, message: newText }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", function () {
    editing = false;
    loadMessages(false);
});

// DELETE / UNSEND ------------------------------------------
$(document).on("click", ".action-delete, .action-unsend", function () {

    const id = $("#msg-action-popup").data("msg-id");

    $.post("../chat/delete_message.php", { id }, () => {
        loadMessages(false);
    });

    closeActionPopup();
});
