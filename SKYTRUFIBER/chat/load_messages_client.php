<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = trim($_POST["ticket"] ?? "");
if (!$ticketId) exit("");

/* FETCH TICKET */
$stmt = $conn->prepare("
    SELECT status FROM tickets
    WHERE id = ? LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("");
if ($ticket["status"] === "resolved") exit("");

/* FETCH CHAT MESSAGES */
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY id ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msgCount = count($messages);

/* Detect first client message */
$firstClientMsg = null;
foreach ($messages as $m) {
    if ($m["sender_type"] === "client") {
        $firstClientMsg = $m;
        break;
    }
}

/* Detect if CSR greeting exists */
$csrGreetingExists = false;
foreach ($messages as $m) {
    if ($m["sender_type"] === "csr" &&
        trim($m["message"]) === "Good day! How may we assist you today?") {
        $csrGreetingExists = true;
        break;
    }
}

/* ------------ PRINT CSR GREETING FIRST ------------ */
if ($firstClientMsg && $csrGreetingExists) {
    echo "
    <div class='message received'>
        <div class='message-avatar'><img src='/upload/default-avatar.png'></div>
        <div class='message-content'>
            <div class='message-bubble'>
                Good day! How may we assist you today?
            </div>
            <div class='message-time'>Just now</div>
        </div>
    </div>
    ";
}

/* ------------ PRINT CHAT MESSAGES (NORMAL) ------------ */
foreach ($messages as $msg) {

    // Skip CSR greeting because we printed it above already
    if (
        $msg["sender_type"] === "csr" &&
        trim($msg["message"]) === "Good day! How may we assist you today?"
    ) {
        continue;
    }

    $id = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$id'>";

    if ($sender === "received") {
        echo "<div class='message-avatar'><img src='/upload/default-avatar.png'></div>";
    }

    echo "<div class='message-content'>";

    if (empty($msg["deleted"])) {
        echo "<div class='message-bubble'>" .
             nl2br(htmlspecialchars(trim($msg["message"]))) .
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
}

/* ------------ PRINT SUGGESTION AFTER CLIENT FIRST MSG ONLY ------------ */
if ($firstClientMsg && $msgCount === 2) {
    echo "
    <div class='message received system-suggest'>
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
}
?>
