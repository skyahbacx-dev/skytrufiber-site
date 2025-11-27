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
    // Remove only if assigned to this CSR
    $stmt = $conn->prepare("UPDATE users SET assigned_csr = NULL WHERE id = :id AND assigned_csr = :csr");
    $stmt->execute([
        ":csr" => $csrUser,
        ":id"  => $clientID
    ]);

    if ($stmt->rowCount() > 0) {
        echo "Client unassigned.";
    } else {
        echo "Cannot unassign. Not assigned to you.";
    }

} catch (PDOException $e) {
    echo "DB ERROR: " . $e->getMessage();
}
?>
