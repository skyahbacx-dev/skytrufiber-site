<?php
session_start();
include "../db_connect.php";

$client_id = $_GET["client_id"] ?? 0;

if (isset($_GET["stop"])) {
    $conn->query("UPDATE clients SET typing_csr = 0 WHERE id = $client_id");
} else {
    $conn->query("UPDATE clients SET typing_csr = 1 WHERE id = $client_id");
}
?>
