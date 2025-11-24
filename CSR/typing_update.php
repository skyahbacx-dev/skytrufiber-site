<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csrUser   = $_SESSION["csr_user"] ?? null;
$client_id = intval($_POST["client_id"] ?? 0);
$csr_typing = intval($_POST["csr_typing"] ?? 0);

if (!$csrUser || !$client_id) {
    echo json_encode(["status"=>"error"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO typing_status (client_id, csr_typing, updated_at)
    VALUES (:cid, :typing, NOW())
    ON CONFLICT (client_id)
    DO UPDATE SET csr_typing = :typing, updated_at = NOW()
");
$stmt->execute([
    ":cid"    => $client_id,
    ":typing" => $csr_typing
]);

echo json_encode(["status"=>"ok"]);
exit;
?>
