<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id      = $_POST["id"] ?? null;
$csr     = $_SESSION["csr_user"] ?? null;
$client  = $_POST["username"] ?? null;

if (!$id) exit("Missing ID");

/* ============================================================
   Identify sender type (CSR or Client)
============================================================ */
if ($csr) {
    $senderType = "csr";
    $identifier = $csr;
} else {
    $senderType = "client";
    $identifier = $client;
}

/* ============================================================
   Validate the message belongs to the sender
============================================================ */
$stmt = $conn->prepare("
    SELECT id FROM chat 
    WHERE id = ? AND sender_type = ?
");
$stmt->execute([$id, $senderType]);

if (!$stmt->fetch()) {
    exit("Permission denied");
}

/* ============================================================
   SOFT DELETE (unsend)
   - Removes text
   - Marks message as deleted
============================================================ */
$del = $conn->prepare("
    UPDATE chat
    SET message = '', deleted = TRUE, edited = FALSE
    WHERE id = ?
");
$del->execute([$id]);

echo "OK";
?>
