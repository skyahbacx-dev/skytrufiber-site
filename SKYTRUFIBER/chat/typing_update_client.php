<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$typing    = $_POST["typing"] ?? 0;

$stmt = $conn->prepare("UPDATE typing_status SET typing=?, updated_at=NOW() WHERE client_id=?");
$stmt->execute([$typing, $client_id]);
