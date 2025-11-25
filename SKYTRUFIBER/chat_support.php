<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: skytrufiber.php");
    exit;
}

$username = $_SESSION["name"] ?? "Guest";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support Chat</title>

<link rel="stylesheet" href="support_chat.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const username = "<?= htmlspecialchars($username, ENT_QUOTES) ?>";
</script>
<script src="support_chat.js"></script>

</head>
<body>

<div class="chat-wrapper">

    <!-- HEADER -->
    <div class="chat-header">
        <img src="default-avatar.png">
        <div class="chat-header-title">
            SkyTruFiber Support <br>
            <small>Online • 24/7 Support</small>
        </div>
    </div>

    <!-- CHAT MESSAGES -->
    <div id="chatBody" class="chat-body"></div>

    <!-- IMAGE PREVIEW -->
    <div id="previewArea" style="padding:10px"></div>

    <!-- INPUT BAR -->
    <div class="chat-input-area">
        <label for="fileInput" class="file-btn"><i class="fa-regular fa-image"></i></label>
        <input type="file" id="fileInput" multiple style="display:none;">
        <input type="text" id="messageInput" class="input-box" placeholder="Type a message…">
        <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
    </div>

</div>

<!-- MEDIA VIEWER -->
<div id="mediaViewer" class="viewer">
    <span id="viewerClose" class="viewer-close">✖</span>
    <img id="viewerImage">
</div>

</body>
</html>
