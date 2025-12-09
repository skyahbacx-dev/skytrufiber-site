<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = intval($_POST["ticket"] ?? 0);
if ($ticketId <= 0) exit("");

// ---------------------------------------------
// FETCH TICKET + CLIENT
// ---------------------------------------------
$stmt = $conn->prepare("
    SELECT status, client_id
    FROM tickets
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("");

// ---------------------------------------------
// AUTO-LOGOUT WHEN RESOLVED
// ---------------------------------------------
if ($ticket["status"] === "resolved") {

    // Set flag for JS to read
    $_SESSION["force_logout"] = true;

    // This output is detected by AJAX, NOT rendered in DOM
    echo "FORCE_LOGOUT";
    exit;
}

// ---------------------------------------------
// FETCH ALL CHAT MESSAGES FOR THIS TICKET
// ---------------------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------
// FIND FIRST CSR MESSAGE + FIRST CLIENT MESSAGE
// ---------------------------------------------
$greetingMsgId = null;
$firstClientMsgId = null;

foreach ($messages as $m) {

    if ($greetingMsgId === null && $m["sender_type"] === "csr") {
        $greetingMsgId = $m["id"];
    }

    if ($firstClientMsgId === null && $m["sender_type"] === "client") {
        $firstClientMsgId = $m["id"];
    }
}

// ---------------------------------------------
// SUGGESTIONS FLAG (set by send_message_client.php)
// ---------------------------------------------
$triggerSuggestion = isset($_SESSION["show_suggestions"]);
unset($_SESSION["show_suggestions"]);

// ---------------------------------------------
// RENDER CHAT MESSAGES
// ---------------------------------------------
foreach ($messages as $msg) {

    $id       = $msg["id"];
    $isCSR    = ($msg["sender_type"] === "csr");
    $sender   = $isCSR ? "received" : "sent";
    $text     = nl2br(htmlspecialchars(trim($msg["message"])));
    $time     = date("g:i A", strtotime($msg["created_at"]));

    // Greeting animation for FIRST CSR message
    $extraClass = ($id == $greetingMsgId) ? " csr-greeting animate-in" : "";

    echo "<div class='message $sender$extraClass' data-msg-id='$id'>";

    // CSR Avatar Only
    if ($isCSR) {
        echo "
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>";
    }

    echo "<div class='message-content'>";

    // Bubble (deleted / normal)
    if (empty($msg["deleted"])) {
        echo "<div class='message-bubble'>$text</div>";
    } else {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    }

    // Time + edited tag
    echo "<div class='message-time'>$time";
    if (!empty($msg["edited"])) {
        echo " <span class='edited-label'>(edited)</span>";
    }
    echo "</div>";

    // Action toolbar (client can edit THEIR OWN messages)
    if (!$isCSR && empty($msg["deleted"])) {
        echo "
        <div class='action-toolbar'>
            <button class='more-btn' data-id='$id'>⋯</button>
        </div>";
    }

    echo "</div></div>";

    // ---------------------------------------------
    // INSERT SUGGESTIONS PACK — ONLY ONCE
    // AFTER FIRST CLIENT MESSAGE
    // ---------------------------------------------
    if (
        $triggerSuggestion &&
        $greetingMsgId !== null &&
        $id == $firstClientMsgId
    ) {

        echo "
        <div class='message received system-suggest animate-in'>
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>

            <div class='message-content'>
                <div class='message-bubble'>
                    Here are some quick answers you may need:
                    <div class='suggest-buttons'>
                        <button class='suggest-btn'>I am experiencing no internet.</button>
                        <button class='suggest-btn'>My connection is slow.</button>
                        <button class='suggest-btn'>My router is blinking red.</button>
                        <button class='suggest-btn'>I already restarted my router.</button>
                        <button class='suggest-btn'>Please assist me. Thank you.</button>
                    </div>
                </div>
                <div class='message-time'>Just now</div>
            </div>
        </div>
        ";

        $triggerSuggestion = false;
    }
}

?>
