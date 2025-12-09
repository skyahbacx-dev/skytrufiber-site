<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = trim($_POST["ticket"] ?? "");
if (!$ticketId) exit("");

// --------------------------------------------------
// FETCH TICKET
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT t.id AS ticket_id, t.status, t.client_id
    FROM tickets t
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("");

if ($ticket["status"] === "resolved") {
    exit("");
}

// --------------------------------------------------
// FETCH CHAT MESSAGES (ordered correctly)
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY id ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msgCount = count($messages);

// --------------------------------------------------
// DETECT FIRST CLIENT MESSAGE & FIRST CSR GREETING
// --------------------------------------------------
$firstClientMessageId = null;
$hasGreeting = false;

foreach ($messages as $m) {
    if ($m["sender_type"] === "csr") {
        // This is CSR greeting IF it's the first CSR message
        if (!$hasGreeting) $hasGreeting = true;
    }
    if ($m["sender_type"] === "client" && $firstClientMessageId === null) {
        $firstClientMessageId = $m["id"];
    }
}

// --------------------------------------------------
// SHOW SUGGESTIONS ONLY ONCE:
//   RIGHT AFTER FIRST CLIENT MESSAGE
//   AND ONLY IF THERE IS ALREADY A CSR GREETING
// --------------------------------------------------
$shouldShowSuggestions = ($hasGreeting && $firstClientMessageId !== null);

// --------------------------------------------------
// RENDER MESSAGES
// --------------------------------------------------
foreach ($messages as $msg) {

    $id     = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time   = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$id'>";

    if ($sender === "received") {
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";
    }

    echo "<div class='message-content'>";

    if (empty($msg["deleted"])) {
        echo "<div class='message-bubble'>"
            . nl2br(htmlspecialchars(trim($msg["message"]))) .
            "</div>";
    } else {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    }

    echo "<div class='message-time'>{$time}";
    if (!empty($msg["edited"])) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    if ($sender === "sent" && empty($msg["deleted"])) {
        echo "<div class='action-toolbar'>
                <button class='more-btn' data-id='$id'>⋯</button>
              </div>";
    }

    echo "</div></div>";

    // --------------------------------------------------
    // INSERT SUGGESTIONS JUST AFTER FIRST CLIENT MESSAGE
    // --------------------------------------------------
    if ($shouldShowSuggestions && $id == $firstClientMessageId) {

        echo "
        <div class='message received system-suggest' data-msg-id='suggest-1'>
            <div class='message-avatar'><img src='/upload/default-avatar.png'></div>
            <div class='message-content'>
                <div class='message-bubble'>
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

        // Make sure suggestions show only once
        $shouldShowSuggestions = false;
    }
}

?>
