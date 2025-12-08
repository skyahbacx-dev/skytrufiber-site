<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");

if (!$username) {
    echo "unresolved";
    exit;
}

// --------------------------------------------------
// FETCH CLIENT
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email ILIKE :u
       OR full_name ILIKE :u
    LIMIT 1
");
$stmt->execute([":u" => $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo "unresolved";
    exit;
}

$client_id = (int)$client['id'];

// --------------------------------------------------
// FETCH LATEST TICKET STATUS
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT status
    FROM tickets
    WHERE client_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$client_id]);
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
