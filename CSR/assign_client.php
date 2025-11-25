<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csr = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csr || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE clients
    SET assigned_csr = :csr
    WHERE id = :cid
");
$stmt->execute([
    ":csr" => $csr,
    ":cid" => $client_id
]);

echo json_encode(["status" => "ok"]);
exit;
?>
