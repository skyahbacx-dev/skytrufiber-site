<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = intval($_POST["client_id"] ?? 0);
if ($clientID <= 0) exit("ERROR");

// Get current lock status
$stmt = $conn->prepare("
    SELECT ticket_lock
    FROM users
    WHERE id = ?
");
$stmt->execute([$clientID]);

$current = $stmt->fetchColumn();
$newLock = ($current == 1) ? 0 : 1;

$update = $conn->prepare("
    UPDATE users
    SET ticket_lock = ?
    WHERE id = ?
");
$update->execute([$newLock, $clientID]);

echo $newLock ? "LOCKED" : "UNLOCKED";
