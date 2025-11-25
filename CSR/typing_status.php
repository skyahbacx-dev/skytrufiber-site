<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = intval($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT typing FROM typing_status WHERE client_id = :cid LIMIT 1");
$stmt->execute([":cid" => $client_id]);

echo json_encode(["typing" => ($stmt->fetchColumn() ?? 0)]);
exit;
