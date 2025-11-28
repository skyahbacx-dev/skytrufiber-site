<?php
// example: load_messages.php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr = $_POST["csr"] ?? null;

if (!$client_id || !$csr) {
    echo "No client specified";
    exit;
}

try {
    $stmt = $conn->prepare("
      SELECT sender_type, message, media_type, media_path, created_at
      FROM chat
      LEFT JOIN chat_media ON chat.id = chat_media.chat_id
      WHERE client_id = ?
      ORDER BY created_at ASC
    ");
    $stmt->execute([$client_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      $cls = ($row['sender_type'] === 'csr') ? 'csr-msg' : 'client-msg';
      $time = date("M d, h:i A", strtotime($row['created_at']));

      if (!empty($row['media_path'])) {
        // it's a media message
        if ($row['media_type'] === 'image') {
          echo "<div class='message $cls'>";
          echo "<img class='chat-image' src='../{$row['media_path']}' alt='Image' />";
          echo "<div class='msg-time ".(($cls==='csr-msg')?'right':'left')."'>$time</div>";
          echo "</div>";
        } else {
          // generic file
          echo "<div class='message $cls'>";
          echo "<a class='file-bubble' href='../{$row['media_path']}' download>Download File</a>";
          if ($row['message']) {
            echo "<div>{$row['message']}</div>";
          }
          echo "<div class='msg-time ".(($cls==='csr-msg')?'right':'left')."'>$time</div>";
          echo "</div>";
        }
      } else {
        // text message
        echo "<div class='message $cls'>";
        echo htmlspecialchars($row['message']);
        echo "<div class='msg-time ".(($cls==='csr-msg')?'right':'left')."'>$time</div>";
        echo "</div>";
      }
    }

} catch (Exception $e) {
    echo "Error loading messages: ".$e->getMessage();
}
