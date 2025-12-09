<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = trim($_POST["ticket"] ?? "");
if (!$ticketId) exit("");

// fetch ticket
$stmt = $conn->prepare("SELECT status FROM tickets WHERE id = ? LIMIT 1");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket || $ticket["status"] === "resolved") exit("");

// fetch messages
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY id ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$firstClientId = null;
foreach ($messages as $m) {
    if ($m["sender_type"] === "client") {
        $firstClientId = $m["id"];
        break;
    }
}

$showSuggestions = isset($_SESSION["show_suggestions"]);
unset($_SESSION["show_suggestions"]);

// render all messages
foreach ($messages as $msg) {

    $id = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $bubble = nl2br(htmlspecialchars(trim($msg["message"])));
    $time = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$id'>";

    if ($sender === "received") {
        echo "<div class='message-avatar'><img src='/upload/default-avatar.png'></div>";
    }

    echo "<div class='message-content'>";
    echo "<div class='message-bubble'>$bubble</div>";
    echo "<div class='message-time'>$time";

    if (!empty($msg["edited"])) echo " <span class='edited-label'>(edited)</span>";

    echo "</div>";

    if ($sender === "sent") {
        echo "<div class='action-toolbar'>
                <button class='more-btn' data-id='$id'>⋯</button>
              </div>";
    }

    echo "</div></div>";

    // insert suggestions AFTER first client message
    if ($showSuggestions && $id == $firstClientId) {

        echo "
        <div class='message received system-suggest'>
            <div class='message-avatar'><img src='/upload/default-avatar.png'></div>
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
    }
}
?>
