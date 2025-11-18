<?php
session_start();
include "../db_connect.php";

$cid = $_POST["client_id"];

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :cid");
$stmt->execute([":cid" => $cid]);
echo "ok";
