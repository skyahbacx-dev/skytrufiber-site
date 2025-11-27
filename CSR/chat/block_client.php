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
    // Mark client as locked
    $stmt = $conn->prepare("UPDATE users SET is_locked = 1 WHERE id = ?");
    $stmt->execute([$clientID]);

    echo "Client locked.";

} catch (PDOException $e) {
    echo "DB ERROR: " . htmlspecialchars($e->getMessage());
}
?>
