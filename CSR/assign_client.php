<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csr = $_SESSION["csr_user"];
$client_id = intval($_POST["client_id"] ?? 0);

if (!$client_id) exit("Invalid client");

$stmt = $conn->prepare("UPDATE users SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([
    ":csr" => $csr,
    ":id" => $client_id
]);

echo "assigned";
?>
