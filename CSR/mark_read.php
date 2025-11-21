<?php
session_start();
include "../db_connect.php";

$client_id = $_POST["client_id"] ?? 0;
if (!$client_id) exit();

$csrUser = $_SESSION["csr_user"] ?? "";

/* Mark client's last message as seen */
$stmt = $conn->prepare("
    UPDATE chat 
    SET seen = 1 
    WHERE client_id = :cid 
    AND sender_type = 'csr'
    AND seen = 0
");
$stmt->execute([":cid" => $client_id]);

echo "ok";
?>
