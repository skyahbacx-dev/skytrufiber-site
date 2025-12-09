<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: text/plain; charset=utf-8");

$ticketId = (int)($_POST["ticket"] ?? 0);
if ($ticketId <= 0) {
    echo "error";
    exit;
}

/* --------------------------------------------------
   1) FETCH TICKET & CLIENT
-------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT client_id
    FROM tickets
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "no-ticket";
    exit;
}

$client_id = (int)$ticket["client_id"];

/* --------------------------------------------------
   2) CHECK IF ANY CHAT MESSAGES EXIST FOR THIS TICKET
-------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM chat
    WHERE ticket_id = ?
");
$stmt->execute([$ticketId]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalMessages = (int)$row["total"];

/* --------------------------------------------------
   3) RETURN RESPONSE
   - empty → system auto-greeting should run
   - has-messages → do nothing
-------------------------------------------------- */
if ($totalMessages === 0) {
    echo "empty";           // No messages → trigger greeting
} else {
    echo "has-messages";    // Messages exist → skip greeting
}
?>
