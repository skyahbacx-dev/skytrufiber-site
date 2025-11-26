<?php
include "../db_connect.php";

$client = $_POST["client_id"];

$sql = "UPDATE users SET assigned_csr = NULL WHERE id = :cid";
$stmt = $pdo->prepare($sql);
$stmt->execute([":cid" => $client]);

echo "ok";
?>
