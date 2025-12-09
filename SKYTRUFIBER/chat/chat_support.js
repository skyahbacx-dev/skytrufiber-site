/* ============================================================
SkyTruFiber Client Chat System — FINAL BUILD (2025)
* CSR greeting always FIRST (backend inserts it before client concern)
* Client message second (backend order is perfect)
* JS will ONLY show suggestions when backend flags it
* Absolutely NO duplicates, NO reordering issues
============================================================ */

/* ---------------- GLOBAL STATE ---------------- */
let editing = false;
let activePopup = null;
const ticketId = new URLSearchParams(window.location.search).get("ticket");
let suggestionShown = false;

/* ============================================================
INIT
============================================================ */
$(document).ready(() => {

    if (!ticketId) {
        $("#chat-messages").html(`
            <p style="text-align:center;padding:20px;color:#777;">Invalid ticket.</p>
        `);
        return;
    }

    // Ensure popup lives inside modal
    const staticPopup = $("#msg-action-popup");
    if (staticPopup.length) $(".chat-modal").append(staticPopup.detach());

    // Load backend-ordered messages
    loadMessages(true);

    // Initial ticket status check
    checkTicketStatus();

    // Poll for updates (safe merge)
    setInterval(() => {
        if (!editing && !activePopup) {
            fetchNewMessages();
            checkTicketStatus();
        }
    }, 3500);

    // Sending
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Theme
    $("#theme-toggle").click(toggleTheme);
});

/* ============================================================
THEME TOGGLE
============================================================ */
function toggleTheme() {
    const root = document.documentElement;
    const dark = root.getAttribute("data-theme") === "dark";
    root.setAttribute("data-theme", dark ? "light" : "dark");
}

/* ============================================================
BACKEND-REQUESTED SUGGESTION BUBBLE
============================================================ */
function insertSuggestionBubble() {
    if (suggestionShown) return;
    suggestionShown = true;

    const bubble = `
        <div class="message received system-suggest" data-msg-id="suggest-1">
            <div class="message-avatar">
                <img src="/SKYTRUFIBER.png">
            </div>
            <div class="message-content">
                <div class="message-bubble">
                    <strong>SkyTru Smart Assistant</strong><br>
                    Please select an option below:
                    <div class="suggest-buttons">
                        <button class="suggest-btn">I am experiencing no internet.</button>
                        <button class="suggest-btn">My connection is slow.</button>
                        <button class="suggest-btn">My router is blinking red.</button>
                        <button class="suggest-btn">I already restarted my router.</button>
                        <button class="suggest-btn">Please assist me. Thank you.</button>
                    </div>
                </div>
                <div class="message-time">Now</div>
            </div>
        </div>
    `;

    $("#chat-messages").append(bubble);
    scrollToBottom();
}

/* ============================================================
SUGGESTION BUTTON CLICK
============================================================ */
$(document).on("click", ".suggest-btn", function () {
    const text = $(this).text();
    $(".system-suggest").remove();

    const tempId = appendClientBubble(text);

    $.post("/SKYTRUFIBER/chat/send_message_client.php", {
        ticket: ticketId,
        message: text
    }).done(() => {
        $(`.message[data-msg-id='${tempId}']`).remove();
        fetchNewMessages();
    });
});

/* ============================================================
TICKET STATUS (RESOLVED HANDLER)
============================================================ */
function checkTicketStatus() {
    $.post("/SKYTRUFIBER/chat/get_ticket_status.php", { ticket: ticketId })
        .done(status => applyTicketStatus(status.trim()));
}

function applyTicketStatus(status) {
    const msgBox = $("#message-input");
    const sendBtn = $("#send-btn");

    if (status === "resolved") {
        msgBox.prop("disabled", true);
        sendBtn.prop("disabled", true);
        $(".system-suggest").remove();

        $("#chat-messages").html(`
            <div class="system-message">
                Your ticket has been marked as 
                <strong style="color:green;">RESOLVED</strong>.<br><br>
                Thank you for contacting SkyTruFiber Support!<br><br>
                <span style="font-size:13px;color:#888;">
                    You will be logged out automatically in 5 seconds...
                </span>
            </div>
        `);

        setTimeout(() => window.location.href = "/SKYTRUFIBER/chat/logout.php", 5000);
        return;
    }

    msgBox.prop("disabled", false);
    sendBtn.prop("disabled", false);
}

/* ============================================================
SEND MESSAGE — Backend decides if suggestions should show
============================================================ */
function sendMessage() {
    const msg = $("#message-input").val().trim();
    if (!msg) return;

    $(".system-suggest").remove();

    const tempId = appendClientBubble(msg);
    $("#message-input").val("");

    $.post("/SKYTRUFIBER/chat/send_message_client.php", {
        ticket: ticketId,
        message: msg
    }).done(raw => {
        let res = {};
        try { res = JSON.parse(raw); } catch {}

        // backend ONLY sets this on FIRST client login message
        if (res.first_message === true) insertSuggestionBubble();

        $(`.message[data-msg-id='${tempId}']`).remove();
        fetchNewMessages();
    });
}

function appendClientBubble(msg) {
    const id = "temp-" + Date.now();
    $("#chat-messages").append(`
        <div class="message sent no-avatar" data-msg-id="${id}">
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
                <div class="message-time">Sending...</div>
            </div>
        </div>
    `);
    scrollToBottom();
    return id;
}

/* ============================================================
LOAD MESSAGES — Backend gives FINAL ORDER
============================================================ */
function loadMessages(scrollBottom = false, callback = null) {
    $.post("/SKYTRUFIBER/chat/load_messages_client.php", { ticket: ticketId })
    .done(html => {

        $("#chat-messages").html(html);
        bindActionToolbar();

        // If more than one message exists → suggestions already consumed
        if ($("#chat-messages .message").length > 1) {
            $(".system-suggest").remove();
        }

        if (scrollBottom) scrollToBottom();
        if (callback) callback();
    });
}

/* ============================================================
FETCH NEW MESSAGES — NO DUPLICATES EVER
============================================================ */
function fetchNewMessages() {
    $.post("/SKYTRUFIBER/chat/load_messages_client.php", { ticket: ticketId })
    .done(html => {

        const temp = $("<div>").html(html);

        temp.find(".message").each(function () {
            const id = $(this).data("msg-id");

            if (!$(`.message[data-msg-id='${id}']`).length) {
                $("#chat-messages").append($(this));
            }
        });

        bindActionToolbar();
    });
}

/* ============================================================
SCROLL CONTROL
============================================================ */
function scrollToBottom() {
    const m = $("#chat-messages");
    m.stop().animate({ scrollTop: m[0].scrollHeight }, 200);
}

/* ============================================================
ACTION TOOLBAR
============================================================ */
function bindActionToolbar() {
    $(".more-btn").off("click").on("click", function (e) {
        e.stopPropagation();
        openPopup($(this).data("id"), this);
    });
}

function openPopup(id, anchor) {
    const popup = $("#msg-action-popup");
    const modal = $(".chat-modal");

    popup.data("msgId", id).show();

    const a = $(anchor).offset();
    const m = modal.offset();

    popup.css({
        top: a.top - m.top + 32,
        left: Math.max(0, a.left - m.left - popup.outerWidth() + 20)
    });

    activePopup = popup;
}

function closePopup() {
    $("#msg-action-popup").hide();
    activePopup = null;
}

/* EDIT & DELETE */
$(document).on("click", ".action-edit", function () {
    startEdit($("#msg-action-popup").data("msgId"));
    closePopup();
});

$(document).on("click", ".action-delete, .action-unsend", function () {
    const id = $("#msg-action-popup").data("msgId");

    $.post("/SKYTRUFIBER/chat/delete_message_client.php", {
        id,
        ticket: ticketId
    }).done(() => loadMessages(false));

    closePopup();
});

/* ============================================================
EDIT MESSAGE
============================================================ */
function startEdit(id) {
    editing = true;
    const bubble = $(`.message[data-msg-id='${id}'] .message-bubble`);
    const old = bubble.text();

    bubble.html(`
        <textarea class="edit-textarea">${old}</textarea>
        <div class="edit-actions">
            <button class="edit-save" data-id="${id}">Save</button>
            <button class="edit-cancel">Cancel</button>
        </div>
    `);
}

$(document).on("click", ".edit-save", function () {
    const id = $(this).data("id");
    const newText = $(this).closest(".message-bubble").find("textarea").val().trim();

    $.post("/SKYTRUFIBER/chat/edit_message_client.php", {
        id,
        ticket: ticketId,
        message: newText
    }).done(() => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", () => {
    editing = false;
    loadMessages(false);
});

/* ============================================================
LOGOUT
============================================================ */
$(document).on("click", "#logout-btn", function () {
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "/SKYTRUFIBER/chat/logout.php";
    }
});
