<?php
session_start();
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

<link rel="stylesheet" href="chat_support.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    const username = "<?= htmlspecialchars($username, ENT_QUOTES) ?>";
</script>

<script src="chat_support.js"></script>
</head>
<body>

<div id="messenger-layout">

    <!-- LEFT PANEL HEADER -->
    <aside id="left-panel">
        <div class="left-header">
            <h3 class="client-title">SUPPORT TEAM</h3>
            <p style="font-size:13px; color:#555;">Chat with our CSR team</p>
        </div>
    </aside>

    <!-- CENTER CHAT PANEL -->
    <main id="chat-panel">

        <!-- CHAT HEADER -->
        <header id="chat-header">
            <div class="chat-user-info">
                <img src="upload/default-avatar.png" class="chat-header-avatar">
                <div>
                    <div class="chat-header-name">SkyTruFiber Support</div>
                    <div class="chat-header-status">
                        <span class="status-dot online"></span> Online
                    </div>
                </div>
            </div>
        </header>

        <!-- CHAT BODY -->
        <section id="chatMessages" class="messages-body"></section>

        <!-- FILE PREVIEW -->
        <section id="previewArea" class="preview-area"></section>

        <!-- INPUT BAR -->
        <footer id="chat-input-bar">
            <label for="fileInput" class="file-upload-icon">
                <i class="fa-regular fa-image"></i>
            </label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" class="message-field" placeholder="Type a message…">
            <button id="sendBtn" class="send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </footer>

    </main>
</div>

<!-- MEDIA VIEWER -->
<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>

</body>
</html>
