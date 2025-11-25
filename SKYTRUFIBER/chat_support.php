<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: skytrufiber.php");
    exit;
}

$client_id = $_SESSION['user'];
$client_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support Chat</title>
<link rel="stylesheet" href="support_chat.css">
</head>

<body>

<div class="chat-container">
    <header class="chat-header">
        <img src="../SKYTRUFIBER.png" class="logo">
        <h2><?php echo htmlspecialchars($client_name); ?></h2>
        <span class="subtext">Connected to CSR Support</span>
    </header>

    <div id="chatMessages" class="chat-messages"></div>

    <div id="typingIndicator" class="typing-indicator" style="display:none;">
        CSR is typing...
    </div>

    <div class="input-container">
        <label class="file-upload">
            <input type="file" id="fileInput" multiple>
            <img src="img/upload.png" class="upload-icon">
        </label>

        <input id="messageInput" type="text" placeholder="Type a message...">

        <button id="sendBtn" class="send-btn">
            <img src="img/send.png">
        </button>
    </div>

    <div id="previewArea" class="preview-area"></div>
</div>

<script>
let clientId = <?php echo $client_id; ?>;
</script>
<script src="client_support.js"></script>
</body>
</html>
