<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();

require_once __DIR__ . "/../../db_connect.php";

$ticketId = intval($_POST["ticket"] ?? 0);
if ($ticketId <= 0) exit("");

// ---------------------------------------------
// FETCH TICKET
// ---------------------------------------------
$stmt = $conn->prepare("
    SELECT status, client_id
    FROM tickets
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("");

// AUTO LOGOUT WHEN RESOLVED
if ($ticket["status"] === "resolved") {
    $_SESSION["force_logout"] = true;
    echo "FORCE_LOGOUT";
    exit;
}

// ---------------------------------------------
// FETCH ALL MESSAGES
// ---------------------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE ticket_id = ?
    ORDER BY id ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine first CSR & client message
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

// Suggestion trigger
$triggerSuggestion = isset($_SESSION["show_suggestions"]);
unset($_SESSION["show_suggestions"]);

// ---------------------------------------------
// RENDER MESSAGES
// ---------------------------------------------
foreach ($messages as $msg) {
    
    $id     = $msg["id"];
    $isCSR  = $msg["sender_type"] === "csr";
    $type   = $isCSR ? "received" : "sent";
    $text   = nl2br(htmlspecialchars($msg["message"]));
    $time   = date("g:i A", strtotime($msg["created_at"]));

    $extraClass = ($id == $greetingMsgId) ? " csr-greeting animate-in" : "";

    echo "<div class='message $type$extraClass' data-msg-id='$id'>";

    if ($isCSR) {
        echo "<div class='message-avatar'><img src='/SKYTRUFIBER.png'></div>";
    }

    echo "<div class='message-content'>";

    if ($msg["deleted"]) {
        echo "<div class='message-bubble removed-text'>Message removed</div>";
    } else {
        echo "<div class='message-bubble'>$text</div>";
    }

    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    if (!$isCSR && !$msg["deleted"]) {
        echo "
        <div class='action-toolbar'>
            <button class='more-btn' data-id='$id'>⋯</button>
        </div>";
    }

    echo "</div></div>";

    // -------------------------------
    // INSERT SUGGESTION PACK
    // -------------------------------
    if (
        $triggerSuggestion &&
        $id == $firstClientMsgId &&
        $greetingMsgId !== null
    ) {

        echo "
        <div class='message received system-suggest animate-in'>
            <div class='message-avatar'><img src='/SKYTRUFIBER.png'></div>
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

        $triggerSuggestion = false;
    }
}
?>
