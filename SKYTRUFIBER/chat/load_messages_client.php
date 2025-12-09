<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = intval($_POST["ticket"] ?? 0);
if ($ticketId <= 0) exit("");

/* --------------------------------------------------
   FETCH TICKET STATUS + CLIENT
-------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT status, client_id
    FROM tickets
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("");

/* --------------------------------------------------
   AUTO-LOGOUT ON RESOLVED
-------------------------------------------------- */
if ($ticket["status"] === "resolved") {

    // Prevent session reuse → force logout on client page
    $_SESSION["force_logout"] = true;

    echo "<script>window.location.href='/SKYTRUFIBER/logout.php';</script>";
    exit;
}

/* --------------------------------------------------
   FETCH CHAT MESSAGES FOR THIS TICKET ONLY
-------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   DETECT GREETING + FIRST CLIENT MESSAGE
-------------------------------------------------- */
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

/* --------------------------------------------------
   SUGGESTION TRIGGER FLAG
-------------------------------------------------- */
$triggerSuggestion = isset($_SESSION["show_suggestions"]);
unset($_SESSION["show_suggestions"]);

/* --------------------------------------------------
   OUTPUT CHAT MESSAGES
-------------------------------------------------- */
foreach ($messages as $msg) {

    $id       = $msg["id"];
    $isCSR    = ($msg["sender_type"] === "csr");
    $sender   = $isCSR ? "received" : "sent";
    $text     = nl2br(htmlspecialchars(trim($msg["message"])));
    $time     = date("g:i A", strtotime($msg["created_at"]));

    // Greeting animation only for the first CSR message
    $extraClass = ($id == $greetingMsgId) ? " csr-greeting animate-in" : "";

    echo "<div class='message $sender$extraClass' data-msg-id='$id'>";

    /* CSR AVATAR */
    if ($isCSR) {
        echo "
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>";
    }

    echo "<div class='message-content'>";

    /* MESSAGE BUBBLE */
    if (empty($msg["deleted"])) {
        echo "<div class='message-bubble'>$text</div>";
    } else {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    }

    /* TIME + EDITED LABEL */
    echo "<div class='message-time'>$time";
    if (!empty($msg["edited"])) {
        echo " <span class='edited-label'>(edited)</span>";
    }
    echo "</div>";

    /* ACTION MENU FOR CLIENT MESSAGES ONLY */
    if (!$isCSR && empty($msg["deleted"])) {
        echo "
        <div class='action-toolbar'>
            <button class='more-btn' data-id='$id'>⋯</button>
        </div>";
    }

    echo "</div></div>";

    /* --------------------------------------------------
       INSERT SUGGESTION BUBBLE (ONLY ONCE)
    -------------------------------------------------- */
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
        </div>";

        $triggerSuggestion = false; // prevent duplication
    }
}

?>
