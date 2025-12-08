/* ============================================================
   SkyTruFiber Client Chat System — FINAL VERSION
   + Auto Logout on Resolved
   + Auto Greeting
   + Quick Suggestions
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
    if (staticPopup.length) {
        $(".chat-modal").append(staticPopup.detach());
    }

    // Insert QUICK SUGGESTIONS (client-side version)
    addQuickSuggestions();

    // Load chat
    loadMessages(true, () => {
        checkFirstTimeGreeting(); // Send greeting if needed
    });

    checkTicketStatus();

    // Poll server
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

    /* THEME */
    $("#theme-toggle").click(toggleTheme);
});

/* ============================================================
   THEME TOGGLE
============================================================ */
function toggleTheme() {
    const root = document.documentElement;
    const isDark = root.getAttribute("data-theme") === "dark";
    root.setAttribute("data-theme", isDark ? "light" : "dark");
}

/* ============================================================
   QUICK SUGGESTIONS
============================================================ */
function addQuickSuggestions() {

    const bar = `
        <div id="quick-suggest" style="
            padding:10px;
            background:#f3f3f3;
            border-top:1px solid #ccc;
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        ">
            <button class="qs-btn">I am experiencing no internet.</button>
            <button class="qs-btn">My connection is slow.</button>
            <button class="qs-btn">My router is blinking red.</button>
            <button class="qs-btn">I already restarted my router.</button>
            <button class="qs-btn">Please assist me. Thank you.</button>
        </div>
    `;

    $(".chat-input-area").before(bar);

    $(document).on("click", ".qs-btn", function () {
        $("#message-input").val($(this).text());
        $("#message-input").focus();
    });
}

/* ============================================================
   AUTO GREETING — FIRST TIME USER
============================================================ */
function checkFirstTimeGreeting() {

    $.post("check_first_message.php", { username }, function (res) {

        if (res.trim() === "empty") {

            // Send greeting message automatically
            $.post("send_message_client.php", {
                username,
                message: "Good day! How may we assist you today?"
            }, () => {
                fetchNewMessages();
            });

        }
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

    if (status === "resolved") {

        $("#message-input").prop("disabled", true);
        $("#send-btn").prop("disabled", true);

        $("#chat-messages").html(`
            <div class="system-message" style="
                text-align:center;
                padding:20px;
                color:#444;
                font-size:15px;
            ">
                Your ticket has been marked as 
                <strong style="color:green;">RESOLVED</strong>.<br><br>
                Thank you for contacting SkyTruFiber Support!<br><br>
                <span style="font-size:13px;color:#888;">
                    You will be logged out automatically in 5 seconds...
                </span>
            </div>
        `);

        // AUTO LOGOUT IN 5 SECONDS
        setTimeout(() => {
            window.location.href = "../chat/logout.php";
        }, 5000);

        return;
    }

    $("#message-input").prop("disabled", false);
    $("#send-btn").prop("disabled", false);
}

/* ============================================================
   SEND MESSAGE
============================================================ */
function sendMessage() {

    const msg = $("#message-input").val().trim();
    if (!msg) return;

    const tempId = appendClientBubble(msg);
    $("#message-input").val("");

    $.post("send_message_client.php", { username, message: msg }, () => {
        $(`.message[data-msg-id='${tempId}']`).remove();
        fetchNewMessages();
    });
}

function appendClientBubble(msg) {
    const tempId = "temp-" + Date.now();

    $("#chat-messages").append(`
        <div class="message sent no-avatar" data-msg-id="${tempId}">
            <div class="message-content">
                <div class="message-bubble">${msg}</div>
                <div class="message-time">Sending...</div>
            </div>
        </div>
    `);

    scrollToBottom();
    return tempId;
}

/* ============================================================
   LOAD MESSAGES
============================================================ */
function loadMessages(scrollBottom = false, callback = null) {

    $.post("load_messages_client.php", { username }, html => {

        $("#chat-messages").html(html);
        bindActionToolbar();

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
        const newMsgs = temp.find(".message");

        newMsgs.each(function () {

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
    $("#chat-messages").stop().animate({ scrollTop: $("#chat-messages")[0].scrollHeight }, 230);
}

$("#scroll-bottom-btn").click(scrollToBottom);

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

    const aOffset = $(anchor).offset();
    const mOffset = modal.offset();

    popup.css({
        top: aOffset.top - mOffset.top + 32,
        left: aOffset.left - mOffset.left - popup.outerWidth() + 20
    });

    activePopup = popup;
}

function closePopup() {
    $("#msg-action-popup").hide();
    activePopup = null;
}

/* POPUP ACTIONS */
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
    const oldText = bubble.text();

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
    const newText = $(this).closest(".message-bubble").find("textarea").val().trim();

    $.post("edit_message_client.php", { id, message: newText }, () => {
        editing = false;
        loadMessages(true);
    });
});

$(document).on("click", ".edit-cancel", function () {
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
