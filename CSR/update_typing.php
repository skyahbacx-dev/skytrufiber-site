<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = (int)($_POST["client_id"] ?? 0);
$typing    = ($_POST["typing"] ?? "false") === "true";

$stmt = $conn->prepare("
    INSERT INTO typing_status (user_id, typing)
    VALUES (:id, :tp)
    ON CONFLICT (user_id) DO UPDATE SET typing = :tp
");
$stmt->execute([
    ":id" => $client_id,
    ":tp" => $typing
]);

echo json_encode(["status" => "ok"]);
exit;
