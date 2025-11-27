<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr       = $_SESSION["csr_user"] ?? null;
$client_id = $_POST["client_id"] ?? null;

if (!$csr || !$client_id) {
    exit("Missing data");
}

try {
    $stmt = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL, is_locked = FALSE
        WHERE id = :cid AND assigned_csr = :csr
    ");
    $stmt->execute([
        ":csr" => $csr,
        ":cid" => $client_id
    ]);

    echo "OK";

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
