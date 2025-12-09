<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$requester = $_POST["requester"] ?? "";

if (!$csr || $clientID <= 0 || !$requester) {
    echo "ERROR";
    exit;
}

// Only assigned CSR can accept
$stmt = $conn->prepare("
    SELECT assigned_csr, transfer_request
    FROM users
    WHERE id = ?
");
$stmt->execute([$clientID]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row["assigned_csr"] !== $csr || $row["transfer_request"] !== $requester) {
    echo "DENIED";
    exit;
}

// Accept transfer
$update = $conn->prepare("
    UPDATE users
    SET assigned_csr = :newcsr,
        transfer_request = NULL
    WHERE id = :id
");
$update->execute([
    ":newcsr" => $requester,
    ":id"     => $clientID
]);

echo "OK";
