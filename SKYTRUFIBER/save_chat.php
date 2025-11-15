<?php
include "../db_connect.php";
header("Content-Type: application/json");

$username = $_POST["client"];
$message = trim($_POST["message"]);

$stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :u LIMIT 1");
$stmt->execute([":u"=>$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$client_id = $row["id"];

$stmt = $conn->prepare("
INSERT INTO chat (client_id, sender_type, message, created_at)
VALUES(:cid, 'client', :msg, NOW())
");
$stmt->execute([":cid"=>$client_id, ":msg"=>$message]);

echo json_encode(["status"=>"ok"]);
