<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$username = $_GET['client'] ?? $_GET['username'] ?? '';

if (!$username) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, message, sender_type, created_at, media_path, media_type, csr_fullname
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([':cid' => $client_id]);

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = [
        "message"     => $row["message"],
        "sender_type" => $row["sender_type"],
        "created_at"  => date("M d g:i A", strtotime($row["created_at"])),
        "media_path"  => $row["media_path"],  // already full URL if uploaded to B2
        "media_type"  => $row["media_type"],
        "csr_fullname" => $row["csr_fullname"]
    ];
}

echo json_encode($messages);
?>
