<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) { echo "Missing"; exit; }

$stmt = $conn->prepare("SELECT typing, user FROM typing_status WHERE client_id = ?");
$stmt->execute([$client_id]);
$status = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($status ?: ["typing" => false]);
