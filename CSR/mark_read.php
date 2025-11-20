<?php
include "../db_connect.php";

$client = $_POST["client_id"] ?? null;

if ($client) {
    $stmt = $conn->prepare("UPDATE messages SET is_read=1 WHERE client_id=? AND sender_type='client'");
    $stmt->execute([$client]);
}
?>
