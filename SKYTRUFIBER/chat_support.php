<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["user"])) {
    header("Location: skytrufiber.php");
    exit;
}

$username = $_SESSION["name"];
$email    = $_SESSION["email"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support Chat</title>
<link rel="stylesheet" href="support_chat.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<div id="chat-container">

    <header id="chat-header">
        <div class="chat-title">
            <img src="../SKYTRUFIBER.png" class="logo">
            <div>
                <h2>Customer Support</h2>
                <p>Logged in as <b><?= htmlspecialchars($username) ?></b></p>
            </div>
        </div>
        <button onclick="location.href='../logout.php'" class="logout-btn">Logout</button>
    </header>

    <main id="chatMessages" class="messages-body"></main>

    <div id="previewArea" class="preview-area"></div>

    <footer id="chat-input-bar">
        <label for="fileInput" class="file-upload-icon">
            <i class="fa-regular fa-image"></i>
        </label>
        <input type="file" id="fileInput" multiple style="display:none;">
        <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off">
        <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
    </footer>

</div>

<!-- MEDIA VIEWER -->
<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>

<script>
const clientUsername = "<?= htmlspecialchars($username) ?>";
</script>

<script src="support_chat.js"></script>
</body>
</html>
