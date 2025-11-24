<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csrUser   = $_SESSION["csr_user"] ?? null;
$client_id = intval($_POST["client_id"] ?? 0);

if (!$csrUser || !$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

// FIND LATEST MESSAGE FOR THIS CLIENT
$stmt = $conn->prepare("
    SELECT id FROM chat
    WHERE client_id = :cid
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([":cid" => $client_id]);
$lastMessageId = $stmt->fetchColumn();

// UPDATE chat_read RECORD
$stmt2 = $conn->prepare("
    INSERT INTO chat_read (client_id, csr, last_read, message_id)
    VALUES (:cid, :csr, NOW(), :mid)
    ON CONFLICT (client_id, csr)
    DO UPDATE SET last_read = NOW(), message_id = EXCLUDED.message_id
");
$stmt2->execute([
    ":cid" => $client_id,
    ":csr" => $csrUser,
    ":mid" => $lastMessageId
]);

// MARK CLIENT MESSAGES AS SEEN
$stmt3 = $conn->prepare("
    UPDATE chat
    SET seen = true
    WHERE client_id = :cid AND sender_type = 'client' AND seen = false
");
$stmt3->execute([":cid" => $client_id]);

echo json_encode(["status"=>"ok"]);
exit;
?>
