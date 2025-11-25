<?php
session_start();
include "../db_connect.php";

$user_id = (int)($_POST["client_id"] ?? 0);
$typing = $_POST["typing"] === "true" ? 1 : 0;

$conn->prepare("UPDATE users SET typing = :t WHERE id = :id")
     ->execute([":t" => $typing, ":id" => $user_id]);
?>
