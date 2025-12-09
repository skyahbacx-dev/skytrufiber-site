<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);

if (!$csr || $clientID <= 0) {
    echo "ERROR";
    exit;
}

$stmt = $conn->prepare("
    UPDATE users
    SET transfer_request = NULL
    WHERE id = ?
");
$stmt->execute([$clientID]);

echo "OK";
