<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csrUser   = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csrUser || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid request"]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([
    ":csr" => $csrUser,
    ":id"  => $client_id
]);

echo json_encode(["status" => "ok"]);
exit;
?>
