<?php
include "../db_connect.php";

$username = $_POST["username"] ?? null;
$msg      = trim($_POST["message"] ?? "");
$media    = $_POST["media"] ?? null;

if (!$username) {
    echo json_encode(["status" => "error", "message" => "No user"]);
    exit;
}

$client = $pdo->prepare("SELECT id FROM users WHERE account_number = ?");
$client->execute([$username]);
$row = $client->fetch();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Unknown user"]);
    exit;
}

$cid = $row["id"];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO chat (client_id, sender_type, message) VALUES (?, 'client', ?) RETURNING id");
    $stmt->execute([$cid, $msg]);
    $chatId = $stmt->fetchColumn();

    if ($media) {
        foreach ($media as $m) {
            $mData = json_decode($m, true);
            $pdo->prepare("INSERT INTO chat_media (chat_id, media_path, media_type) VALUES (?, ?, ?)")
                ->execute([$chatId, $mData["url"], $mData["type"]]);
        }
    }

    $pdo->commit();
    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
