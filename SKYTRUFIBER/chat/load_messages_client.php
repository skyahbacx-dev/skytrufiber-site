<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
if (!$username) exit("");

// --------------------------------------------------
// FIND CLIENT BY EMAIL OR FULL NAME
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, full_name, ticket_status
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("");

$client_id      = (int)$client["id"];
$ticket_status  = $client["ticket_status"] ?? "unresolved";

// --------------------------------------------------
// If RESOLVED → return nothing (JS handles logout + message)
// --------------------------------------------------
if ($ticket_status === "resolved") {
    exit("");
}

// --------------------------------------------------
// FETCH CHAT MESSAGES
//---------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// Check if NO messages yet → send Assistant Suggestion Bubble
// --------------------------------------------------
$is_first_time = (count($messages) === 0);

// If first-time user → return system suggestion bubble
if ($is_first_time) {

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
                    <button class='suggest-btn' data-msg='I am experiencing no internet.'>No internet</button>
                    <button class='suggest-btn' data-msg='My connection is slow.'>Slow connection</button>
                    <button class='suggest-btn' data-msg='My router is blinking red.'>Router blinking red</button>
                    <button class='suggest-btn' data-msg='I already restarted my router.'>Restarted router</button>
                    <button class='suggest-btn' data-msg='Please assist me. Thank you.'>Need assistance</button>
                </div>
            </div>
            <div class='message-time'>Just now</div>
        </div>
    </div>
    ";

    exit(); // Stop here — no user messages exist yet
}

// --------------------------------------------------
// RENDER EXISTING CHAT MESSAGES
//--------------------------------------------------
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

    // --------------------------
    // MESSAGE BUBBLE
    // --------------------------
    if (!$msg["deleted"]) {

        $text = trim($msg["message"]);

        echo "<div class='message-bubble'>";
        echo nl2br(htmlspecialchars($text));
        echo "</div>";

    } else {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    }

    // --------------------------
    // TIME + EDITED
    // --------------------------
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // --------------------------
    // ACTION TOOLBAR
    // --------------------------
    echo "<div class='action-toolbar'>";
    if ($sender === "sent" && !$msg["deleted"]) {
        echo "<button class='more-btn' data-id='$id'>⋯</button>";
    }
    echo "</div>";

    echo "</div>"; // content
    echo "</div>"; // wrapper
}

?>
