<?php
session_start();
include "../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr_typing = $_POST["csr_typing"] ?? null;
$client_typing = $_POST["client_typing"] ?? null;

if (!$client_id) exit("missing client id");

if ($csr_typing !== null) {
    $pdo->prepare("UPDATE typing_status SET typing = :t WHERE client_id = :cid")
        ->execute([":t" => $csr_typing, ":cid" => $client_id]);
}

if ($client_typing !== null) {
    $pdo->prepare("UPDATE typing_status SET typing = :t WHERE client_id = :cid")
        ->execute([":t" => $client_typing, ":cid" => $client_id]);
}

echo "ok";
