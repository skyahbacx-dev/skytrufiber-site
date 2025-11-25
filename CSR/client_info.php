<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csrUser   = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_GET["id"] ?? 0);

if (!$csrUser || !$client_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT full_name, email, district, barangay, date_installed FROM users WHERE id = :id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([":id" => $client_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([]);
    exit;
}

echo json_encode([
    "name"      => $user["full_name"],
    "email"     => $user["email"],
    "district"  => $user["district"],
    "barangay"  => $user["barangay"],
    "installed" => $user["date_installed"]
]);
exit;
?>
