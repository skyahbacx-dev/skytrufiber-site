<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json; charset=utf-8");

$csrUser = $_SESSION["csr_user"] ?? null;
$clientId = $_GET["client_id"] ?? 0;

if (!$csrUser || !$clientId) {
    echo json_encode([]);
    exit;
}

// =======================================================
// Mark client messages as seen and update last_read
// =======================================================
$markSeen = $conn->prepare("
    UPDATE chat
    SET seen = true
    WHERE client_id = :cid
      AND sender_type = 'client'
");
$markSeen->execute([":cid" => $clientId]);

// Update chat_read record
$updateRead = $conn->prepare("
    INSERT INTO chat_read (client_id, csr, last_read)
    VALUES (:cid, :csr, NOW())
    ON CONFLICT (client_id, csr)
    DO UPDATE SET last_read = EXCLUDED.last_read
");
$updateRead->execute([
    ":cid" => $clientId,
    ":csr" => $csrUser
]);

// =======================================================
// Load all messages and attached media
// =======================================================
$stmt = $conn->prepare("
    SELECT
        m.id,
        m.sender_type,
        m.message,
        m.created_at,
        cm.media_path,
        cm.media_type
    FROM chat m
    LEFT JOIN chat_media cm ON cm.chat_id = m.id
    WHERE m.client_id = :cid
    ORDER BY m.created_at ASC
");
$stmt->execute([":cid" => $clientId]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
exit;
?>
