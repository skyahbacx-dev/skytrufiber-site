<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

$stmt = $conn->prepare("SELECT typing FROM typing_status WHERE client_id=?");
$stmt->execute([$client_id]);

echo ($stmt->fetchColumn() == 1) ? "1" : "0";
