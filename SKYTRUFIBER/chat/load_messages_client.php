<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = trim($_POST["ticket"] ?? "");
if (!$ticketId) exit("");

// --------------------------------------------------
// FETCH TICKET & CLIENT
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT t.id AS ticket_id, t.status, t.client_id, u.full_name
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("");

$ticket_status = $ticket["status"] ?? "unresolved";

// --------------------------------------------------
// If RESOLVED → return nothing (JS handles logout + message)
// --------------------------------------------------
if ($ticket_status === "resolved") {
    exit("");
}

// --------------------------------------------------
// FETCH CHAT MESSAGES
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY id ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// DETECT LOGIN-FIRST-MESSAGE SCENARIO
//
// We show the greeting ONLY if:
//   • EXACTLY 1 client message exists (the login concern)
//   • ZERO CSR messages
//   • No CSR auto-greet was added by send_message_client.php
// --------------------------------------------------
$csrCount = 0;
$clientCount = 0;

foreach ($messages as $m) {
    if ($m["sender_type"] === "csr") $csrCount++;
    if ($m["sender_type"] === "client") $clientCount++;
}

$showGreeting = ($csrCount === 0 && $clientCount === 1);

if ($showGreeting) {
    echo "
    <div class='message received system-suggest'>
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>
        <div class='message-content'>
            <div class='message-bubble'>
                Welcome to SkyTruFiber Support! 😊<br>
                Here are some quick answers you might be looking for:
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
}

// --------------------------------------------------
// RENDER EXISTING CHAT HISTORY
// --------------------------------------------------
foreach ($messages as $msg) {

    $id     = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time   = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$id'>";

    // CSR Avatar
    if ($sender === "received") {
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";
    }

    echo "<div class='message-content'>";

    // MESSAGE BUBBLE
    if (empty($msg["deleted"])) {
        $text = trim($msg["message"]);
        echo "<div class='message-bubble'>" . nl2br(htmlspecialchars($text)) . "</div>";
    } else {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    }

    // TIME + EDITED LABEL
    echo "<div class='message-time'>{$time}";
    if (!empty($msg["edited"])) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // ACTION TOOLBAR (only for client’s own messages)
    echo "<div class='action-toolbar'>";
    if ($sender === "sent" && empty($msg["deleted"])) {
        echo "<button class='more-btn' data-id='$id'>⋯</button>";
    }
    echo "</div>";

    echo "</div>"; // message-content
    echo "</div>"; // message wrapper
}

?>
