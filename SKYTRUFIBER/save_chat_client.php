<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$username = $_POST["username"] ?? '';
$message  = trim($_POST["message"] ?? '');
$sender   = "client";

if ($message === "" && empty($_FILES['file']['name'])) {
    echo json_encode(["status" => "error", "msg" => "Empty"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $ins = $conn->prepare("INSERT INTO clients (name, created_at) VALUES (:n, NOW()) RETURNING id");
    $ins->execute([':n' => $username]);
    $client_id = $ins->fetchColumn();
} else {
    $client_id = $data["id"];
}

$media_path = null;
$media_type = null;

if (!empty($_FILES['file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $media_type = 'image';
        $folder = "../uploads/chat_images/";
    } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
        $media_type = 'video';
        $folder = "../uploads/chat_videos/";
    }

    if ($media_type) {
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $path = $folder . $newName;
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
        $media_path = substr($path, 3);
    }
}

$stmt2 = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
    VALUES (:cid, 'client', :msg, :mp, :mt, NOW())
");
$stmt2->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":mp"  => $media_path,
    ":mt"  => $media_type
]);

echo json_encode(["status" => "ok", "client_id" => $client_id]);
exit;
?>
