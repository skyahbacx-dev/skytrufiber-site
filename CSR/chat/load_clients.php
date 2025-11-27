<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
if (!$csr) exit(json_encode(["status" => false, "message" => "Unauthorized"]));

$stmt = $conn->prepare("
    SELECT id, full_name, email, is_online, assigned_csr
    FROM users
    ORDER BY full_name ASC
");
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => true, "clients" => $clients]);
