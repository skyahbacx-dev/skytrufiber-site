<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . "/../../db_connect.php";

$ticketId = intval($_POST["ticket"] ?? 0);

if ($ticketId <= 0) {
    echo "unresolved";
    exit;
}

$stmt = $conn->prepare("SELECT status FROM tickets WHERE id = ? LIMIT 1");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

echo $ticket["status"] ?? "unresolved";
