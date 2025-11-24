<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json; charset=utf-8");

$username = $_GET["client"] ?? null;

if (!$username) {
    echo json_encode([]);
    exit;
}

// GET CLIENT ID
$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :n LIMIT 1");
$stmt->execute([":n" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode([]);
    exit;
}

// FETCH CHAT MESSAGES
$sql = "
    SELECT
        c.id,
        c.message,
        c.sender_type,
        c.created_at,
        c.seen
    FROM chat c
    WHERE c.client_id = :cid
    ORDER BY c.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([":cid"=>$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH MEDIA PER CHAT MESSAGE
$sql2 = "
    SELECT
        cm.chat_id,
        cm.media_path,
        cm.media_type
    FROM chat_media cm
    JOIN chat c ON cm.chat_id = c.id
    WHERE c.client_id = :cid
    ORDER BY cm.id ASC
";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute([":cid"=>$client_id]);
$media = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// MERGE MEDIA
foreach ($messages as &$msg) {
    $msg["media"] = [];

    foreach ($media as $m) {
        if ($m["chat_id"] == $msg["id"]) {
            $msg["media"][] = [
                "media_path" => $m["media_path"],
                "media_type" => $m["media_type"],
            ];
        }
    }
}

echo json_encode($messages);
exit;
?>
