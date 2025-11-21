<?php

if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>

<div class="client-panel">
    <input class="search" placeholder="Search clients..." id="searchInput">
    <div id="clientList" class="client-list"></div>
</div>

<div class="chat-panel" id="chatPanel">

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

    <div class="chat-box" id="chatMessages"></div>

    <div id="overlayLock" class="chat-locked hidden">
        <div id="overlayText">⚠ Select or assign client to start chatting</div>
    </div>

    <div class="chat-input">
        <label for="fileInput" class="upload-icon">
            <i class="fa-regular fa-image"></i>
        </label>
        <input type="file" id="fileInput" multiple style="display:none;">
        <input type="text" id="messageInput" placeholder="Type anything....." disabled>
        <button id="sendBtn" class="send-btn" disabled>
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>

</div>

<aside id="clientInfoPanel" class="client-info-panel">
    <button class="close-info" onclick="toggleClientInfo()">✖</button>
    <h3>Client Information</h3>
    <p><strong id="infoName"></strong></p>
    <p id="infoEmail"></p>
    <p><b>District:</b> <span id="infoDistrict"></span></p>
    <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
</aside>
