<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$currentOwner = $_POST["current_owner"] ?? "";

if (!$csr || $clientID <= 0) {
    echo "ERROR";
    exit;
}

// Save transfer request for the owner
$stmt = $conn->prepare("
    UPDATE users
    SET transfer_request = :csr
    WHERE id = :id
");
$stmt->execute([
    ":csr" => $csr,
    ":id"  => $clientID
]);

echo "OK";
