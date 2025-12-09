<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = trim($_POST["ticket"] ?? "");
if (!$ticketId) exit("");

/* --------------------------------------------------
   FETCH TICKET
-------------------------------------------------- */
$stmt = $conn->prepare("SELECT status FROM tickets WHERE id = ? LIMIT 1");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket || $ticket["status"] === "resolved") exit("");

/* --------------------------------------------------
   FETCH CHAT MESSAGES IN ORDER
-------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY id ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msgCount = count($messages);

/* --------------------------------------------------
   DETECT GREETING + FIRST CLIENT MESSAGE
-------------------------------------------------- */
$firstClientMsgId = null;
$greetingMsgId = null;

foreach ($messages as $m) {

    // First CSR message = greeting
    if ($m["sender_type"] === "csr" && $greetingMsgId === null) {
        $greetingMsgId = $m["id"];
    }

    // First client message
    if ($m["sender_type"] === "client" && $firstClientMsgId === null) {
        $firstClientMsgId = $m["id"];
    }
}

/* --------------------------------------------------
   TRIGGERED SUGGESTION FLAG FROM LOGIN
-------------------------------------------------- */
$triggerSuggestion = isset($_SESSION["show_suggestions"]);
unset($_SESSION["show_suggestions"]);

/* --------------------------------------------------
   RENDER MESSAGES
-------------------------------------------------- */
foreach ($messages as $msg) {

    $id       = $msg["id"];
    $sender   = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $isClient = ($sender === "sent");
    $text     = nl2br(htmlspecialchars(trim($msg["message"])));
    $time     = date("g:i A", strtotime($msg["created_at"]));

    // Apply animation class only to greeting + new messages
    $extraClass = ($id == $greetingMsgId) ? " csr-greeting animate-in" : "";

    echo "<div class='message $sender$extraClass' data-msg-id='$id'>";

    // Avatar for CSR only
    if ($sender === "received") {
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";
    }

    echo "<div class='message-content'>";

    // Message bubble
    if (empty($msg["deleted"])) {
        echo "<div class='message-bubble'>$text</div>";
    } else {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    }

    // Timestamp + edited flag
    echo "<div class='message-time'>$time";
    if (!empty($msg["edited"])) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // Action toolbar for client messages only
    if ($isClient && empty($msg["deleted"])) {
        echo "<div class='action-toolbar'>
                <button class='more-btn' data-id='$id'>⋯</button>
              </div>";
    }

    echo "</div></div>";

    /* --------------------------------------------------
       INSERT SUGGESTION BUBBLE AFTER FIRST CLIENT MESSAGE:
       - Only once
       - Only if greeting exists
       - Only on login first message trigger
    -------------------------------------------------- */
    if (
        $triggerSuggestion &&
        $greetingMsgId !== null &&
        $id == $firstClientMsgId
    ) {

        echo "
        <div class='message received system-suggest animate-in' data-msg-id='suggest-1'>
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

        // Prevent repetition on polling
        $triggerSuggestion = false;
    }
}

?>
