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
    SET assigned_csr = NULL
    WHERE id = :cid AND assigned_csr = :csr
");
$stmt->execute([
    ":cid" => $client_id,
    ":csr" => $csr
]);

echo json_encode(["status" => "ok"]);
exit;
?>
