<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$ticketId = (int)($_POST["ticket"] ?? 0);

if (!$ticketId) {
    echo "unresolved";
    exit;
}

// --------------------------------------------------
// FETCH TICKET STATUS
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT status
    FROM tickets
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "unresolved";
    exit;
}

// --------------------------------------------------
// RETURN STATUS
// --------------------------------------------------
$status = $ticket['status'] ?? 'unresolved';
echo $status;
?>
