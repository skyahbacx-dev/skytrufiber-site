/* ============================================================
SkyTruFiber Client Chat System — UPDATED 2025
* Fixed auto-greet workflow, path issues, added AJAX error handling
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
            <p style="text-align:center;padding:20px;color:#777;">
                Invalid ticket.
            </p>
        `);
        return;
    }

    // Move popup inside modal
    const staticPopup = $("#msg-action-popup");
    if (staticPopup.length) $(".chat-modal").append(staticPopup.detach());

    // Load chat (no more auto-greet check here)
    loadMessages(true);

    // Check ticket status initially
    checkTicketStatus();

    // Poll server for new messages & ticket status
    setInterval(() => {
        if (!editing && !activePopup) {
            fetchNewMessages();
            checkTicketStatus();
        }
    }, 3500);

    // Send message
    $("#send-btn").click(sendMessage);
    $("#message-input").keypress(e => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Theme toggle
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
SMART ASSISTANT SUGGESTION BUBBLE
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
                <div class="message-bubble" style="background:#e8f3ff;color:#003c75;">
                    <strong>SkyTru Smart Assistant</strong><br>
                    Please select an option below:
                    <div class="suggest-buttons" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:6px;">
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

// Suggestion clicked → auto-send
$(document).on("click", ".suggest-btn", function () {
    const text = $(this).text();
    $(".system-suggest").remove();

    const tempId = appendClientBubble(text);

    $.post("/SKYTRUFIBER/chat/send_message_client.php", { ticket: ticketId, message: text })
        .done(() => {
            $(`.message[data-msg-id='${tempId}']`).remove();
            fetchNewMessages();
        })
        .fail(() => {
            alert("Failed to send message. Please try again.");
        });
});

/* ============================================================
AUTO GREETING — REMOVED FROM JS (PHP handles it now)
============================================================ */
// ★ REMOVED checkFirstTimeGreeting()
// ★ Auto-greet now triggers ONLY inside send_message_client.php
// ★ JS no longer controls welcome message

/* ============================================================
TICKET STATUS CONTROL
============================================================ */
function checkTicketStatus() {
    $.post("/SKYTRUFIBER/chat/get_ticket_status.php", { ticket: ticketId }, function (status) {
        applyTicketStatus(status.trim());
    }).fail(() => console.warn("Failed to fetch ticket status."));
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
SEND MESSAGE
============================================================ */
function sendMessage() {
    const msg = $("#message-input").val().trim();
    if (!msg) return;

    $(".system-suggest").remove();

    const tempId = appendClientBubble(msg);
    $("#message-input").val("");

    $.post("/SKYTRUFIBER/chat/send_message_client.php", { ticket: ticketId, message: msg })
        .done((data) => {
            // NEW: If server responded that it's the first message → show suggestions
            try {
                const res = JSON.parse(data);
                if (res.first_message === true) {
                    insertSuggestionBubble();
                }
            } catch {}

            $(`.message[data-msg-id='${tempId}']`).remove();
            fetchNewMessages();
        })
        .fail(() => alert("Failed to send message. Please try again."));
}

/* … REST OF THE FILE UNCHANGED … */

