<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr       = $_SESSION["csr_user"] ?? null;
$client_id = $_POST["client_id"] ?? null;

if (!$csr || !$client_id) {
    exit("Missing data");
}

try {
    // Assign client to CSR and UNLOCK it
    $stmt = $conn->prepare("
        UPDATE users
        SET assigned_csr = :csr,
            is_locked = FALSE
        WHERE id = :cid
    ");
    $stmt->execute([
        ":csr" => $csr,
        ":cid" => $client_id
    ]);

    echo "OK";

} catch (PDOException $e) {
    echo "DB Error: " . htmlspecialchars($e->getMessage());
}
