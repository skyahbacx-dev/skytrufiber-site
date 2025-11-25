<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["name"])) {
    header("Location: skytrufiber.php");
    exit;
}

$username = $_SESSION["name"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support — <?= htmlspecialchars($username) ?></title>

<link rel="stylesheet" href="support_chat.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const currentUser = "<?= htmlspecialchars($username, ENT_QUOTES) ?>";
</script>
<script src="client_support.js"></script>
</head>

<body>

<div id="client-chat-layout">

    <!-- HEADER -->
    <header id="client-chat-header">
        <div class="header-left">
            <img src="../SKYTRUFIBER.png" class="chat-logo">
            <h2>Support Chat</h2>
        </div>
        <div class="header-right">
            <b><?= htmlspecialchars($username) ?></b>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- CHAT BODY -->
    <section id="clientMessages" class="messages-body"></section>

    <!-- FILE PREVIEW -->
    <section id="previewArea" class="preview-area"></section>

    <!-- INPUT BAR -->
    <footer id="clientInputBar">
        <label for="fileInput" class="file-upload-icon">
            <i class="fa-regular fa-image"></i>
        </label>
        <input type="file" id="fileInput" multiple style="display:none;">
        <input type="text" id="messageInput" placeholder="Type a message...">
        <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
    </footer>

</div>

<!-- MEDIA VIEWER -->
<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>

</body>
</html>
