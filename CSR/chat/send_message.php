<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;
$message  = trim($_POST["message"] ?? "");

// Incoming media files (CSR upload)
$files = $_FILES["media"] ?? null;

// Validate basic input
if (!$csrUser || !$clientID) {
    echo json_encode(["status" => "error", "msg" => "Missing credentials"]);
    exit;
}

// Prevent empty message with no media
if ($message === "" && (!$files || empty($files["name"][0]))) {
    echo json_encode(["status" => "error", "msg" => "Message empty"]);
    exit;
}

try {

    /* ============================================================
       1️⃣ INSERT MAIN MESSAGE ROW
    ============================================================ */
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, deleted, edited, delivered, seen, created_at)
        VALUES (:cid, 'csr', :msg, FALSE, FALSE, FALSE, FALSE, NOW())
    ");

    $stmt->execute([
        ":cid" => $clientID,
        ":msg" => $message
    ]);

    $chatID = $conn->lastInsertId(); // IMPORTANT


    /* ============================================================
       2️⃣ HANDLE MULTIPLE MEDIA UPLOADS (if any)
    ============================================================ */
    if ($files && !empty($files["name"][0])) {

        $uploadDir = "../../chat_uploads/";

        // Create folder if missing
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        for ($i = 0; $i < count($files["name"]); $i++) {

            $tmpName = $files["tmp_name"][$i];
            if (!is_uploaded_file($tmpName)) continue;

            $original = $files["name"][$i];
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

            // Determine media type
            $type = "file";
            if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) $type = "image";
            if (in_array($ext, ["mp4", "mov", "avi", "mkv", "webm"])) $type = "video";

            $newName = uniqid("media_") . "." . $ext;
            $path = $uploadDir . $newName;

            move_uploaded_file($tmpName, $path);

            // Insert into media table
            $m = $conn->prepare("
                INSERT INTO chat_media (chat_id, media_type, file_path)
                VALUES (?, ?, ?)
            ");
            $m->execute([$chatID, $type, $newName]);
        }
    }

    echo json_encode([
        "status" => "ok",
        "chat_id" => $chatID
    ]);
    exit;

} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "msg" => $e->getMessage()
    ]);
    exit;
}
?>
