<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csrUser   = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csrUser || !$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

/* Mark all unread client messages as seen */
$stmt = $conn->prepare("
    UPDATE chat
    SET seen = true
    WHERE client_id = :cid
      AND sender_type = 'client'
      AND seen = false
");
$stmt->execute([":cid" => $client_id]);

echo json_encode(["status" => "ok"]);
exit;
?>
