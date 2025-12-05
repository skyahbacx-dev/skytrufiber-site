<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;
$message   = trim($_POST["message"] ?? "");

if (!$client_id || !$csr) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

/* ============================================================
   CREATE CHAT MESSAGE FIRST
   If text is empty but media exists → create empty placeholder ""
============================================================ */
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (:cid, 'csr', :msg, TRUE, FALSE, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message
]);

$chatId = $conn->lastInsertId();


/* ============================================================
   IF NO MEDIA → TEXT-ONLY MESSAGE
============================================================ */
if (!isset($_FILES["media"]) || empty($_FILES["media"]["name"][0])) {
    echo json_encode([
        "status" => "ok",
        "chat_id" => $chatId,
        "msg" => "Text saved"
    ]);
    exit;
}


/* ============================================================
   PROCESS ALL UPLOADED FILES
============================================================ */
foreach ($_FILES["media"]["name"] as $i => $originalName) {

    $tmpPath = $_FILES["media"]["tmp_name"][$i];
    $mime = $_FILES["media"]["type"][$i];
    $error = $_FILES["media"]["error"][$i];
    $size = $_FILES["media"]["size"][$i];

    // Skip invalid uploads
    if ($error !== UPLOAD_ERR_OK || !$tmpPath || !file_exists($tmpPath)) {
        continue;
    }

    // Basic validation
    if ($size > 50 * 1024 * 1024) { // 50MB limit
        continue;
    }

    // Determine file type
    $mediaType = "file";
    if (strpos($mime, "image") === 0)      $mediaType = "image";
    elseif (strpos($mime, "video") === 0)  $mediaType = "video";

    // Read raw file for BLOB storage
    $blob = file_get_contents($tmpPath);
    if ($blob === false) continue;

    // Unique storage filename
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $uniqueName = time() . "_" . bin2hex(random_bytes(6)) . "." . $ext;

    // Insert into DB
    $insert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, created_at)
        VALUES (:chat, :path, :type, :blob, NOW())
    ");
    $insert->bindValue(":chat", $chatId, PDO::PARAM_INT);
    $insert->bindValue(":path", $uniqueName, PDO::PARAM_STR);
    $insert->bindValue(":type", $mediaType, PDO::PARAM_STR);
    $insert->bindValue(":blob", $blob, PDO::PARAM_LOB);
    $insert->execute();
}


/* ============================================================
   DONE — MEDIA + TEXT SAVED
============================================================ */
echo json_encode([
    "status" => "ok",
    "chat_id" => $chatId,
    "msg" => "Upload complete"
]);

exit;
?>
