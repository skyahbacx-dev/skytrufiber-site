<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = (int)($_POST["ticket"] ?? 0);
if ($ticketId <= 0) exit("error");

// --------------------------------------------------
// FETCH TICKET & CLIENT
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT t.id AS ticket_id, t.client_id
    FROM tickets t
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    exit("no-ticket");
}

$client_id = (int)$ticket["client_id"];

// --------------------------------------------------
// CHECK IF ANY MESSAGES EXIST FOR THIS TICKET
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM chat
    WHERE ticket_id = ?
");
$stmt->execute([$ticketId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// --------------------------------------------------
// RETURN STATUS
// --------------------------------------------------
if ((int)$row["total"] === 0) {
    echo "empty";        // First message → trigger greeting
} else {
    echo "has-messages"; // Messages exist
}
?>
