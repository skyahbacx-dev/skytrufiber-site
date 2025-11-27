<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
$csrUser  = $_SESSION["csr_user"] ?? null;

if (!$clientID) {
    echo "<p style='padding:10px; color:#777'>No client selected</p>";
    exit;
}

$stmt = $conn->prepare("
    SELECT sender_type, message, media, created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $clientID]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) {
    echo "<p style='padding:10px; color:#777'>Start conversation now...</p>";
    exit;
}

foreach ($messages as $m):
    $sender = $m["sender_type"];
    $msg    = htmlspecialchars($m["message"]);
    $media  = $m["media"];
    $time   = date("h:i A", strtotime($m["created_at"]));
    
    // Determine message alignment
    $class = ($sender === "csr") ? "msg-csr" : "msg-client";
?>

<div class="chat-bubble <?= $class ?>">

    <?php if ($media): ?>
        <?php
        $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));

        if (in_array($ext, ["jpg","jpeg","png","gif"])) {
            echo "<img class='chat-img' src=\"../upload/chat_images/$media\">";
        }
        else if (in_array($ext, ["mp4","mov","avi"])) {
            echo "<video class='chat-video' controls src=\"../upload/chat_videos/$media\"></video>";
        }
        else if ($ext === "pdf") {
            echo "<a class='chat-file' href=\"../upload/chat_files/$media\" target='_blank'>
                    📄 View PDF
                  </a>";
        } else {
            echo "<a class='chat-file' href=\"../upload/chat_files/$media\" download>
                    📎 Download File
                  </a>";
        }
        ?>
    <?php endif; ?>

    <?php if ($msg): ?>
        <p><?= $msg ?></p>
    <?php endif; ?>

    <small class="chat-time"><?= $time ?></small>
</div>

<?php endforeach; ?>
