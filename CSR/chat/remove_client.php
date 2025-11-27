<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) exit("Missing");

$stmt = $conn->prepare("UPDATE users SET assigned_csr = NULL, is_online = FALSE WHERE id = ?");
$stmt->execute([$client_id]);

echo "OK";
