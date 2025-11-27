<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;

if (!$csrUser || !$clientID) {
    echo "Invalid request.";
    exit;
}

try {
    // Assign only if currently unassigned
    $stmt = $conn->prepare("UPDATE users SET assigned_csr = :csr WHERE id = :id AND assigned_csr IS NULL");
    $stmt->execute([
        ":csr" => $csrUser,
        ":id"  => $clientID
    ]);

    if ($stmt->rowCount() > 0) {
        echo "Client assigned.";
    } else {
        echo "Client already assigned.";
    }

} catch (PDOException $e) {
    echo "DB ERROR: " . $e->getMessage();
}
?>
