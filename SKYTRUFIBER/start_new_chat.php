<?php
include '../db_connect.php';
$username = $_GET['username'] ?? '';
if (!$username) exit;

// Archive previous session by updating chat_sessions
$conn->query("UPDATE chat_sessions SET archived=1 WHERE client_name='$username'");

// (No deletion – CSR keeps records)
echo json_encode(['status'=>'archived']);
?>
