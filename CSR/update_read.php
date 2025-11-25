<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csr_user  = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

$conn->prepare("
    UPDATE chat
    SET seen = true
    WHERE user_id = :cid AND sender_type = 'client'
")->execute([":cid" => $client_id]);

echo json_encode(["status" => "ok"]);
exit;
