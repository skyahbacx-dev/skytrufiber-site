<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? "";
$client = $_POST["client_id"] ?? null;

if ($client && $csrUser) {
    $stmt = $conn->prepare("
        INSERT INTO chat_read (client_id, csr, last_read)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$client, $csrUser]);
}
?>
