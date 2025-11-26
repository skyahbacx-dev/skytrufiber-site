<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    echo json_encode(["status" => "error", "message" => "Not authorized"]);
    exit;
}

$csr  = $_SESSION["csr_user"];
$cid  = $_POST["client_id"] ?? null;
$msg  = trim($_POST["message"] ?? "");
$media = $_POST["media"] ?? null;

if (!$cid) {
    echo json_encode(["status" => "error", "message" => "No client"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen)
        VALUES (?, 'csr', ?, TRUE, FALSE)
        RETURNING id
    ");
    $stmt->execute([$cid, $msg]);
    $chatId = $stmt->fetchColumn();

    if ($media) {
        foreach ($media as $m) {
            $mData = json_decode($m, true);
            $pdo->prepare("INSERT INTO chat_media (chat_id, media_path, media_type) VALUES (?, ?, ?)")
                ->execute([$chatId, $mData["url"], $mData["type"]]);
        }
    }

    $pdo->prepare("UPDATE chat_read SET last_read = NOW() WHERE client_id = ? AND csr = ?")
        ->execute([$cid, $csr]);

    $pdo->commit();
    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
