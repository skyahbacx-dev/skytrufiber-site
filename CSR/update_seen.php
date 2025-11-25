<?php
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = (int)($_POST["client_id"] ?? 0);

$stmt = $conn->prepare("
    UPDATE chat SET seen = true
    WHERE client_id = :cid AND sender_type='csr'
");
$stmt->execute([":cid" => $client_id]);

echo json_encode(["status"=>"ok"]);
exit;
