<?php

if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>

<!-- LEFT: CLIENT LIST -->
<div class="client-panel">
    <input class="search" placeholder="Search clients..." id="searchInput">
    <div id="clientList" class="client-list"></div>
</div>

<!-- CENTER: CHAT PANEL -->
<div class="chat-panel" id="chatPanel">

    <!-- CHAT HEADER -->
    <div class="chat-header">
        <div class="user-section">
            <img id="chatAvatar" src="upload/default-avatar.png" class="chat-avatar">
            <div>
                <div id="chatName" class="chat-name">Select a client</div>
                <div id="chatStatus" class="chat-status">
                    <span id="statusDot" class="status-dot offline"></span> Offline
                </div>
            </div>
        </div>

        <button class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
    </div>

    <!-- CHAT MESSAGES -->
    <div class="chat-box" id="chatMessages">
        <p class="placeholder">👈 Select a client to start chatting</p>
    </div>

    <!-- FILE PREVIEW -->
    <div id="previewArea" class="preview-area"></div>

    <!-- INPUT BOX -->
    <div class="chat-input">
        <label for="fileInput" class="upload-icon">
            <i class="fa-regular fa-image"></i>
        </label>
        <input type="file" id="fileInput" multiple style="display:none;">
        <input type="text" id="messageInput" placeholder="Type a message...">
        <button id="sendBtn" class="send-btn">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- RIGHT: CLIENT INFO PANEL -->
<aside id="clientInfoPanel" class="client-info-panel">
    <button class="close-info" onclick="toggleClientInfo()">✖</button>
    <h3>Client Information</h3>
    <p><strong id="infoName"></strong></p>
    <p id="infoEmail"></p>
    <p><b>District:</b> <span id="infoDistrict"></span></p>
    <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
</aside>
