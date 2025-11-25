<?php
include "../db_connect.php";
header("Content-Type: application/json");

$client_name = $_POST["username"] ?? "";
$typing = (int)($_POST["typing"] ?? 0);

$stmt = $conn->prepare("UPDATE clients SET typing = :t WHERE name = :u");
$stmt->execute([
    ":t" => $typing,
    ":u" => $client_name
]);

echo json_encode(["status" => "ok"]);
exit;
