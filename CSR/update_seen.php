<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION['csr_user'] ?? null;
$clientId = $_POST['client_id'] ?? null;

if (!$csr || !$clientId) exit("fail");

$stmt = $conn->prepare("
    UPDATE chat
    SET seen = TRUE
    WHERE client_id = :client_id
      AND sender_type = 'client'
      AND seen = FALSE
");
$stmt->execute([":client_id" => $clientId]);

echo "OK";
