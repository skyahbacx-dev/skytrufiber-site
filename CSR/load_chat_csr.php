<?php
session_start();
require "../db_connect.php";

$clientId = $_POST["client_id"] ?? null;

if (!$clientId) {
    echo json_encode(["status" => "error", "message" => "Missing client ID"]);
    exit;
}

$sql = "
    SELECT c.id, c.message, c.sender_type, c.delivered, c.seen, c.created_at,
           m.media_path, m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON c.id = m.chat_id
    WHERE c.client_id = :client_id
    ORDER BY c.created_at ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":client_id" => $clientId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// group messages by date
$grouped = [];
foreach ($rows as $row) {
    $date = date("M d, Y", strtotime($row["created_at"]));
    if (!isset($grouped[$date])) $grouped[$date] = [];
    $grouped[$date][] = $row;
}

// update read tracking
$csr = $_SESSION["csr_user"] ?? null;
$update = $pdo->prepare("
    INSERT INTO chat_read (client_id, csr, last_read)
    VALUES (:client, :csr, CURRENT_TIMESTAMP)
    ON CONFLICT (client_id, csr)
    DO UPDATE SET last_read = CURRENT_TIMESTAMP
");
$update->execute([":client" => $clientId, ":csr" => $csr]);

echo json_encode(["status" => "success", "messages" => $grouped]);
s
