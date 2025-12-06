<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr       = $_SESSION["csr_user"] ?? null;
$client_id = $_POST["client_id"] ?? null;

if (!$csr || !$client_id) {
    exit("Missing data");
}

try {
    // Only unassign if THIS CSR currently owns the client
    $stmt = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL,
            is_locked = TRUE   -- lock client once unassigned
        WHERE id = :cid
        AND assigned_csr = :csr
    ");
    $stmt->execute([
        ":csr" => $csr,
        ":cid" => $client_id
    ]);

    echo "OK";

} catch (PDOException $e) {
    echo "DB Error: " . htmlspecialchars($e->getMessage());
}
