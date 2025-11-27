<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) exit("0");

$stmt = $conn->prepare("SELECT typing FROM typing_status WHERE client_id = ? LIMIT 1");
$stmt->execute([$client_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo $row ? $row["typing"] : 0;
