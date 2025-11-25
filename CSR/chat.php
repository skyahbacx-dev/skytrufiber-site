<?php

if (!isset($_SESSION["csr_user"])) {
    header("Location: csr_login.php");
    exit;
}
$csrUser = $_SESSION["csr_user"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR CHAT — SkyTruFiber</title>
<link rel="stylesheet" href="chat.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const csrUser = "<?php echo $csrUser; ?>";
</script>
</head>

<body>

<div id="messenger-layout">

    <!-- LEFT SIDEBAR CLIENT LIST -->
    <div id="left-panel">
        <div class="left-header">
            <input id="searchInput" type="text" placeholder="Search clients..." class="search-clients"
                   onkeyup="loadClients(this.value)">
        </div>
        <div id="clientList" class="client-scroll"></div>
    </div>

    <!-- CENTER CHAT PANEL -->
    <div id="chat-panel">

        <!-- HEADER -->
        <div id="chat-header">
            <div class="chat-user-info">
                <img id="chatAvatar" src="upload/default-avatar.png" class="chat-header-avatar">
                <div>
                    <div id="chatName" class="chat-header-name">Select a client</div>
                    <div class="chat-header-status">
                        <span class="status-dot offline"></span>
                        <span id="statusText">Offline</span>
                    </div>
                </div>
            </div>
            <button class="info-btn" onclick="toggleClientInfo()">ℹ️</button>
        </div>

        <!-- CHAT BODY -->
        <div id="chatMessages" class="messages-body"></div>
        <div id="typingIndicator" class="typing-indicator">
            <div class="typing-bubble">
                <div class="dot"></div><div class="dot"></div><div class="dot"></div>
            </div>
        </div>

        <!-- FILE PREVIEW -->
        <div id="previewArea" class="preview-area"></div>

        <!-- INPUT BAR -->
        <div id="chat-input-bar">
            <span class="file-upload-icon"><i class="fa-regular fa-image"></i></span>
            <input type="file" id="fileInput" hidden multiple>
            <input type="text" id="messageInput" placeholder="Write a message..." class="message-field">
            <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- RIGHT INFO PANEL -->
    <div id="infoPanel" class="right-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <img src="upload/default-avatar.png" id="infoAvatar" class="info-avatar">
        <h3 id="infoName" style="text-align:center;margin-top:10px;"></h3>
        <p id="infoEmail"></p>
        <p id="infoDistrict"></p>
        <p id="infoBrgy"></p>
    </div>

</div>

<!-- MEDIA VIEWER -->
<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script src="csr_chat.js"></script>
</body>
</html>
