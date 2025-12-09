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
$stmt = $conn->prepare("SELECT status FROM tickets WHERE id = ? LIMIT 1");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket || $ticket["status"] === "resolved") exit("");

// --------------------------------------------------
// FETCH CHAT MESSAGES IN CORRECT ORDER
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
// DETECT:
//   1. First client message ID
//   2. Whether CSR greeting exists
// --------------------------------------------------
$firstClientMsgId = null;
$hasGreeting = false;

foreach ($messages as $m) {

    if ($m["sender_type"] === "csr" && !$hasGreeting) {
        $hasGreeting = true;
    }

    if ($m["sender_type"] === "client" && $firstClientMsgId === null) {
        $firstClientMsgId = $m["id"];
    }
}

// --------------------------------------------------
// SESSION FLAG FROM LOGIN → triggers FIRST suggestions
// --------------------------------------------------
$triggerSuggestion = isset($_SESSION["show_suggestions"]);
unset($_SESSION["show_suggestions"]);

// --------------------------------------------------
// RENDER CHAT MESSAGES
// --------------------------------------------------
foreach ($messages as $msg) {

    $id       = $msg["id"];
    $sender   = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $text     = nl2br(htmlspecialchars(trim($msg["message"])));
    $time     = date("g:i A", strtotime($msg["created_at"]));
    $isClient = ($sender === "sent");

    echo "<div class='message $sender' data-msg-id='$id'>";

    // Avatar only for CSR
    if ($sender === "received") {
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";
    }

    echo "<div class='message-content'>";

    // Bubble
    echo "<div class='message-bubble'>$text</div>";

    // Time + Edited label
    echo "<div class='message-time'>$time";
    if (!empty($msg["edited"])) {
        echo " <span class='edited-label'>(edited)</span>";
    }
    echo "</div>";

    // Action toolbar only for client's own messages
    if ($isClient && empty($msg["deleted"])) {
        echo "<div class='action-toolbar'>
                <button class='more-btn' data-id='$id'>⋯</button>
              </div>";
    }

    echo "</div></div>";

    // --------------------------------------------------
    // INSERT SUGGESTION BUBBLE ONLY ONCE:
    //   right after FIRST client message
    //   ONLY IF greeting exists
    //   ONLY IF login flagged suggestion trigger
    // --------------------------------------------------
    if (
        $triggerSuggestion &&
        $hasGreeting &&
        $id == $firstClientMsgId
    ) {

        echo "
        <div class='message received system-suggest' data-msg-id='suggest-1'>
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
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

        // ensure it prints only once, even during polling
        $triggerSuggestion = false;
    }
}

?>
