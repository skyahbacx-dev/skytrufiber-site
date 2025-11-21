<?php
session_start();
include "../db_connect.php";

if (isset($_POST["typing"])) {
    $stmt = $conn->prepare("
        REPLACE INTO typing_status (client_id, csr, typing)
        VALUES (:cid, :csr, :t)
    ");
    $stmt->execute([
        ":cid" => $_POST["client_id"],
        ":csr" => $_POST["csr"],
        ":t"   => $_POST["typing"]
    ]);
    exit("ok");
}

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) exit("0");

$stmt = $conn->prepare("SELECT typing FROM typing_status WHERE client_id = :cid LIMIT 1");
$stmt->execute([":cid" => $client_id]);
echo $stmt->fetchColumn() ?? "0";
