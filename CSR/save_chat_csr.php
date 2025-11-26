<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$csrUser = $_SESSION['csr_user'];  // example "CSR1"
$client_id = $_POST["client_id"] ?? null;
$message   = $_POST["message"] ?? "";
$user      = $_POST["user"] ?? "";   // CLIENT EMAIL / ACCOUNT NUMBER

if (!$client_id) {
    echo json_encode(["error" => "Missing client id"]);
    exit;
}

// ========== INSERT MESSAGE FIRST ==========
$stmt = $pdo->prepare("INSERT INTO chat (client_id, sender_type, message, delivered, seen, user)
                       VALUES (:cid, 'csr', :msg, FALSE, FALSE, :usr)
                       RETURNING id");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":usr" => $user
]);

$chat_id = $stmt->fetchColumn();

// ============ HANDLE MEDIA UPLOAD ============
if (!empty($_FILES["media"]["name"][0])) {

    $bucketName = "ahba-chat-media";
    $endpoint   = "https://s3.us-east-005.backblazeb2.com";
    $keyId      = "005a548887f9c4f0000000002";
    $appKey     = "K005fOYaprINPto/Qdm9wex0w4v/L2k";

    foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {

        $filename = time() . "_" . basename($_FILES["media"]["name"][$i]);
        $b2Path   = "chat_uploads/" . $filename;

        $fileData = file_get_contents($tmp);

        $curl = curl_init("$endpoint/$bucketName/$b2Path");
        curl_setopt_array($curl, [
            CURLOPT_PUT => true,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "$keyId:$appKey",
            CURLOPT_INFILESIZE => strlen($fileData),
            CURLOPT_POSTFIELDS => $fileData,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $result = curl_exec($curl);
        curl_close($curl);

        $url = "$endpoint/$bucketName/$b2Path";

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $type = (in_array($ext, ['jpg','jpeg','png','gif','webp'])) ? "image" : "video";

        $insertMedia = $pdo->prepare(
            "INSERT INTO chat_media (chat_id, media_path, media_type) VALUES (:cid, :path, :type)"
        );
        $insertMedia->execute([
            ":cid" => $chat_id,
            ":path" => $url,
            ":type" => $type
        ]);
    }
}

echo json_encode(["status" => "ok"]);
?>
