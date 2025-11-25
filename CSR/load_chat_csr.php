<?php
session_start();
include "../db_connect.php";

header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$csr_user  = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_GET["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode([]);
    exit;
}

// Load chat + attached media files
$sql = "
    SELECT c.id AS chat_id, c.sender_type, c.message, c.created_at,
           m.media_path, m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON m.chat_id = c.id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([":cid" => $client_id]);

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = [
        "chat_id"     => $row["chat_id"],
        "sender_type" => $row["sender_type"],
        "message"     => $row["message"],
        "media_path"  => $row["media_path"],
        "media_type"  => $row["media_type"],
        "created_at"  => date("M d h:i A", strtotime($row["created_at"]))
    ];
}

// Update read status for CSR
$conn->prepare("
    INSERT INTO chat_read (client_id, csr, last_read)
    VALUES (:cid, :csr, NOW())
    ON CONFLICT (client_id, csr)
    DO UPDATE SET last_read = NOW()
")->execute([
    ":cid" => $client_id,
    ":csr" => $csr_user
]);

echo json_encode($messages);
exit;
?>
