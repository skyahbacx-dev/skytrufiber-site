<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id      = $_POST["id"] ?? null;
$message = trim($_POST["message"] ?? "");
$csr     = $_SESSION["csr_user"] ?? null;
$client  = $_POST["username"] ?? null;

if (!$id) exit("Missing ID");

// Prevent empty edited message
if ($message === "") {
    exit("Message cannot be empty");
}

/* ============================================================
   Determine sender who is editing the message
============================================================ */
if ($csr) {
    $senderType = "csr";
    $identifier = $csr;
} else {
    $senderType = "client";
    $identifier = $client;
}

/* ============================================================
   Validate that this sender OWNS the message
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
   UPDATE the text message
============================================================ */
$update = $conn->prepare("
    UPDATE chat
    SET message = ?, edited = TRUE
    WHERE id = ?
");
$update->execute([$message, $id]);

echo "OK";
?>
