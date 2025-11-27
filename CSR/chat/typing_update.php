<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_POST["csr"] ?? null;
$typing    = isset($_POST["typing"]) ? (int)$_POST["typing"] : 0;

if (!$client_id || !$csr) {
    exit("Missing data");
}

try {
    // Check if record already exists
    $check = $conn->prepare("
        SELECT id FROM typing_status WHERE client_id = ?
    ");
    $check->execute([$client_id]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $update = $conn->prepare("
            UPDATE typing_status
            SET typing = ?, updated_at = NOW()
            WHERE client_id = ?
        ");
        $update->execute([$typing, $client_id]);
    } else {
        $insert = $conn->prepare("
            INSERT INTO typing_status (client_id, typing, updated_at)
            VALUES (?, ?, NOW())
        ");
        $insert->execute([$client_id, $typing]);
    }

    echo "OK";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
