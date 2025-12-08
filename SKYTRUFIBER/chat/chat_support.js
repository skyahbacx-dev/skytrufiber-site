/* ============================================================
   SkyTruFiber Client Chat System — FINAL VERSION (2025)
   + Auto Logout on Resolved
   + Auto Greeting
   + Floating Quick Suggestions (Auto-Send on Click)
   + Mobile Optimized
============================================================ */

/* ---------------- GLOBAL STATE ---------------- */
let editing = false;
let activePopup = null;
const username = new URLSearchParams(window.location.search).get("username");

/* ============================================================
   INIT
============================================================ */
$(document).ready(() => {

    if (!username) {
        $("#chat-messages").html(`
            <p style="text-align:center;padding:20px;color:#777;">
                Invalid user.
            </p>
        `);
        return;
    }

    // Move popup
    const staticPopup = $("#msg-action-popup");
    if (staticPopup.length) $(".chat-modal").append(staticPopup.detach());

    // Floating Quick Reply Buttons
    insertQuickReplies();

    // Load chat then auto-greet if first time
    loadMessages(true, () => {
        checkFirstTimeGreeting();
    });

    // Ticket status polling
    checkTicketStatus();

    // Poll server every 3.5 seconds
    setInterval(() => {
        if (!editing && !activePopup) {
            fetchNewMessages();
            checkTicketStatus();
        }
    }, 3500);

    /* SEND MESSAGE */
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    /* THEME TOGGLE */
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
   FLOATING QUICK SUGGESTIONS
============================================================ */
function insertQuickReplies() {

    const html = `
        <div id="quick-replies" class="quick-suggestions">
            <button class="qr-btn">I am experiencing no internet.</button>
            <button class="qr-btn">My connection is slow.</button>
            <button class="qr-btn">My router is blinking red.</button>
            <button class="qr-btn">I already restarted my router.</button>
            <button class="qr-btn">Please assist me. Thank you.</button>
        </div>
    `;

    $(html).insertBefore(".chat-input-area");

    // AUTO-SEND QUICK REPLY
    $(document).on("click", ".qr-btn", function () {
        const text = $(this).text();
        $("#message-input").val(text);
        sendMessage(); // sends immediately
    });
}

// Hide quick replies on first message sent
function hideQuickReplies() {
    $("#quick-replies").slideUp(200);
}

/* ============================================================
   AUTO GREETING — FIRST TIME USER
============================================================ */
function checkFirstTimeGreeting() {
    $.post("check_first_message.php", { username }, function (res) {
        if (res.trim() !== "empty") return;

        $.post("send_message_client.php", {
            username,
            message: "Good day! How may we assist you today?"
        }, () => fetchNewMessages());
    });
}

/* ============================================================
   TICKET STATUS CONTROL
============================================================ */
function checkTicketStatus() {
    $.post("get_ticket_status.php", { username }, function (status) {
        applyTicketStatus(status.trim());
    });
}

function applyTicketStatus(status) {

    const msgBox = $("#message-input");
    const sendBtn = $("#send-btn");

    if (status === "resolved") {

        msgBox.prop("disabled", true);
        sendBtn.prop("disabled", true);
        $("#quick-replies").remove();

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

        // AUTO LOGOUT
        setTimeout(() => {
            window.location.href = "../chat/logout.php";
        }, 5000);

        return;
    }

    msgBox.prop("disabled", false);
    sendBtn.prop("disabled", false);
}

/* ============================================================
   SEND MESSAGE
============================================================ */
function sendMessage() {

    const msg = $("#message-input").val().trim();
    if (!msg) return;

    hideQuickReplies(); // hide suggestions after first message

    const tempId = appendClientBubble(msg);
    $("#message-input").val("");

    $.post("send_message_client.php", { username, message: msg }, () => {
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
   LOAD MESSAGES
============================================================ */
function loadMessages(scrollBottom = false, callback = null) {

    $.post("load_messages_client.php", { username }, html => {

        const wasEmpty = $("#chat-messages").children().length === 0;

        $("#chat-messages").html(html);
        bindActionToolbar();

        // If CSR sent something first, hide suggestions
        if (!wasEmpty) hideQuickReplies();

        if (scrollBottom) scrollToBottom();
        if (callback) callback();
    });
}

/* ============================================================
   FETCH NEW MESSAGES
============================================================ */
function fetchNewMessages() {
    $.post("load_messages_client.php", { username }, html => {

        const temp = $("<div>").html(html);
        const incoming = temp.find(".message");

        incoming.each(function () {
            const id = $(this).data("msg-id");
            if ($(`.message[data-msg-id='${id}']`).length) return;

            $("#chat-messages").append($(this));
        });

        bindActionToolbar();
    });
}

/* ============================================================
   SCROLLING
============================================================ */
function scrollToBottom() {
    const m = $("#chat-messages");
    m.stop().animate({ scrollTop: m[0].scrollHeight }, 230);
}

$("#scroll-bottom-btn").click(scrollToBottom);

/* ============================================================
   ACTION TOOLBAR (EDIT / DELETE)
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
        left: a.left - m.left - popup.outerWidth() + 20
    });

    activePopup = popup;
}

function closePopup() {
    $("#msg-action-popup").hide();
    activePopup = null;
}

/* Popup actions */
$(document).on("click", ".action-edit", function () {
    startEdit($("#msg-action-popup").data("msgId"));
    closePopup();
});

$(document).on("click", ".action-unsend, .action-delete", function () {
    const id = $("#msg-action-popup").data("msgId");
    $.post("delete_message_client.php", { id, username }, () => loadMessages(false));
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

    $.post("edit_message_client.php", { id, message: newText }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", () => {
    editing = false;
    loadMessages(false);
});

/* ============================================================
   LOGOUT BUTTON
============================================================ */
$(document).on("click", "#logout-btn", function () {
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "../chat/logout.php";
    }
});
