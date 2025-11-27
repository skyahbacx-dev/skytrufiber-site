<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) exit;

// Fetch messages
$stmt = $conn->prepare("
    SELECT sender_type, message, media, created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $clientID]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch online and typing states
$info = $conn->prepare("
    SELECT is_online, is_typing
    FROM users
    WHERE id = ?
    LIMIT 1
");
$info->execute([$clientID]);
$clientState = $info->fetch(PDO::FETCH_ASSOC);

$return = [
    "messages" => "",
    "is_online" => $clientState["is_online"] ?? 0,
    "is_typing" => $clientState["is_typing"] ?? 0
];

if (!$messages) {
    $return["messages"] = "<p style='padding:10px;color:#666'>Start conversation...</p>";
    echo json_encode($return);
    exit;
}

$html = "";
foreach ($messages as $m) {
    $sender = $m["sender_type"];
    $msg    = htmlspecialchars($m["message"]);
    $media  = $m["media"];
    $time   = date("h:i A", strtotime($m["created_at"]));

    $class = ($sender === "csr") ? "msg-csr" : "msg-client";

    $html .= "<div class='chat-bubble $class'>";

    if ($media) {
        $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));

        if (in_array($ext, ["jpg","jpeg","png","gif"])) {
            $html .= "<img class='chat-img' src=\"../upload/chat_images/$media\">";
        } elseif (in_array($ext, ["mp4","mov","avi"])) {
            $html .= "<video class='chat-video' controls src=\"../upload/chat_videos/$media\"></video>";
        } elseif ($ext === "pdf") {
            $html .= "<a class='chat-file' href=\"../upload/chat_files/$media\" target='_blank'>📄 View PDF</a>";
        } else {
            $html .= "<a class='chat-file' download href=\"../upload/chat_files/$media\">📎 Download File</a>";
        }
    }

    if ($msg) $html .= "<p>$msg</p>";
    $html .= "<small class='chat-time'>$time</small>";
    $html .= "</div>";
}

$return["messages"] = $html;

echo json_encode($return);
