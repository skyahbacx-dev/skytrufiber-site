<?php
session_start();
require "../db_config.php";

$clientId = $_POST["client_id"] ?? null;

$sql = "
    SELECT id, full_name, email, district, barangay, is_online, assigned_csr
    FROM users WHERE id = :id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $clientId]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

$info["assigned"] = ($info["assigned_csr"] === $_SESSION["csr_user"]) ? "yes" : "no";

echo json_encode($info);
