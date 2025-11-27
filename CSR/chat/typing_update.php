<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$typing    = $_POST["typing"] ?? 0;
$csr       = $_SESSION["csr_user"] ?? null;

if (!$client_id || !$csr) {
    echo "Missing required data.";
    exit;
}

try {
    // Ensure row exists
    $check = $conn->prepare("SELECT id FROM typing_status WHERE client_id = ? LIMIT 1");
    $check->execute([$client_id]);

    if ($check->rowCount() == 0) {
        $insert = $conn->prepare("
            INSERT INTO typing_status (client_id, typing, user, updated_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insert->execute([$client_id, $typing, $csr]);
    } else {
        $update = $conn->prepare("
            UPDATE typing_status SET typing = ?, user = ?, updated_at = NOW()
            WHERE client_id = ?
        ");
        $update->execute([$typing, $csr, $client_id]);
    }

    echo "OK";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage();
}
