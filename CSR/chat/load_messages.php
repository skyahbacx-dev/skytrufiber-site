<?php
require_once "../../db_connect.php";

$client_id = $_GET["client_id"] ?? null;
if (!$client_id) exit("Missing client ID");

$stmt = $conn->prepare("
    SELECT c.id, c.sender_type, c.message, c.created_at,
           cm.media_path, cm.media_type
    FROM chat c
    LEFT JOIN chat_media cm ON cm.chat_id = c.id
    WHERE c.client_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$client_id]);

while ($msg = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cls = $msg["sender_type"] === "csr" ? "chat-bubble csr-message" : "chat-bubble";

    echo "<div class='$cls'>";

    if (!empty($msg["message"])) {
        echo nl2br(htmlspecialchars($msg["message"])) . "<br>";
    }

    // If media exists
    if (!empty($msg["media_path"])) {
        $path = "../../" . $msg["media_path"];

        if ($msg["media_type"] === "image") {
            echo "<img src='$path' style='width:180px;border-radius:8px;cursor:pointer;' onclick='window.open(\"$path\")'>";
        } elseif ($msg["media_type"] === "video") {
            echo "<video style='width:240px;border-radius:10px;' controls>
                    <source src='$path'>
                  </video>";
        } else {
            $fileLabel = basename($msg["media_path"]);
            echo "<a href='$path' download style='display:inline-block;padding:8px;background:#007bff;color:#fff;border-radius:8px;margin-top:5px;'>
                    <i class='fa fa-download'></i> $fileLabel
                  </a>";
        }
    }

    $time = date("M d h:i A", strtotime($msg["created_at"]));
    echo "<div style='font-size:10px;opacity:0.6;'>$time</div>";

    echo "</div>";
}
?>
