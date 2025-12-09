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
    SELECT id, status, client_id
    FROM tickets
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("");
if ($ticket["status"] === "resolved") exit("");

// --------------------------------------------------
// FETCH CHAT (CSR GREET ALWAYS FIRST, THEN CLIENT MSG)
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
// DETECT FIRST CSR GREETING & FIRST CLIENT MESSAGE
// --------------------------------------------------
$firstClientMsgId = null;
$hasGreeting = false;

foreach ($messages as $msg) {

    if ($msg["sender_type"] === "csr" && !$hasGreeting) {
        $hasGreeting = true;
    }

    if ($msg["sender_type"] === "client" && $firstClientMsgId === null) {
        $firstClientMsgId = $msg["id"];
    }
}

// --------------------------------------------------
// ONLY SHOW SUGGESTIONS IF:
//   ✔ A CSR greeting exists
//   ✔ A client first message exists
//   ✔ And suggestions have not been shown before in this render
// --------------------------------------------------
$shouldShowSuggestions = ($hasGreeting && $firstClientMsgId !== null);

// --------------------------------------------------
// RENDER CHAT HISTORY
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
    // INSERT SUGGESTION BUBBLE AFTER FIRST CLIENT MSG ONLY
    // --------------------------------------------------
    if ($shouldShowSuggestions && $id == $firstClientMsgId) {

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

        // Only show ONCE
        $shouldShowSuggestions = false;
    }
}

?>
