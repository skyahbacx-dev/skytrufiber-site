<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? "";
if (!$username) exit("");

// ----------------------------------
// FIND CLIENT BY email OR full name
// ----------------------------------
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

$client_id = (int)$client["id"];
$ticket_status = $client["ticket_status"] ?? "unresolved";

// ----------------------------------------------------------
// IMPORTANT: If ticket is resolved → return EMPTY MESSAGES
// ----------------------------------------------------------
if ($ticket_status === "resolved") {
    // Client side script will detect empty messages and clear chat
    exit("");
}

// ----------------------------------
// FETCH CHAT MESSAGES (TEXT ONLY)
// ----------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------
// RENDER MESSAGES (NO MEDIA, NO REACTIONS)
// ----------------------------------
foreach ($messages as $msg) {

    $id      = $msg["id"];
    $sender  = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time    = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$id'>";

    // CSR avatar
    if ($sender === "received") {
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";
    }

    echo "<div class='message-content'>";

    // --------------------------
    // TEXT BUBBLE
    // --------------------------
    if (!$msg["deleted"]) {

        $text = trim($msg["message"]);
        if ($text !== "") {
            echo "<div class='message-bubble'>";
            echo nl2br(htmlspecialchars($text));
            echo "</div>";
        }

    } else {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    }

    // --------------------------
    // TIME + (edited)
    // --------------------------
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // --------------------------
    // ACTION TOOLBAR (NO REACTIONS)
    // --------------------------
    echo "<div class='action-toolbar'>";
    if ($sender === "sent" && !$msg["deleted"]) {
        echo "<button class='more-btn' data-id='$id'>⋯</button>";
    }
    echo "</div>";

    echo "</div>"; // message-content
    echo "</div>"; // wrapper
}
?>
