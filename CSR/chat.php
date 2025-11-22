<?php
session_start();
if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}
?>

<!-- ================================
     LEFT CLIENT LIST
================================ -->
<div class="client-panel">
    <input class="search" placeholder="Search clients..." id="searchInput" onkeyup="loadClients(this.value)">
    <div id="clientList" class="client-list"></div>
</div>

<!-- ================================
     CHAT PANEL — CENTER
================================ -->
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
        <button class="info-btn" onclick="toggleClientInfo()"><i class="fa fa-info-circle"></i></button>
    </div>

    <!-- MESSAGES -->
    <div class="chat-box" id="chatMessages">
        <p class="placeholder-message">👈 Select a client to start chat</p>
    </div>

    <!-- PREVIEW BEFORE SEND -->
    <div id="previewArea" class="preview-area"></div>

    <!-- CHAT INPUT -->
    <div class="chat-input">
        <label for="fileInput" class="upload-icon">
            <i class="fa-regular fa-image"></i>
        </label>
        <input type="file" id="fileInput" multiple accept="image/*,video/*" hidden>
        <input type="text" id="messageInput" placeholder="Type a message...">
        <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
    </div>

</div>

<!-- ================================
     RIGHT CLIENT INFO PANEL
================================ -->
<aside id="clientInfoPanel" class="client-info-panel">
    <div class="client-info-header">
        <h3>Client Info</h3>
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
    </div>

    <p><strong>Name:</strong> <span id="infoName"></span></p>
    <p><strong>Email:</strong> <span id="infoEmail"></span></p>
    <p><strong>District:</strong> <span id="infoDistrict"></span></p>
    <p><strong>Barangay:</strong> <span id="infoBrgy"></span></p>
</aside>

<!-- ================================
     MEDIA VIEW MODAL — WITH GALLERY
================================ -->
<div id="mediaModal" class="media-modal">
    <span id="closeMediaModal" class="close-modal">✖</span>

    <div class="media-display-container">
        <img id="mediaDisplay" class="media-display">
    </div>

    <div id="mediaThumbnails" class="media-thumbnails"></div>

    <a id="downloadMedia" download class="download-btn">⬇ Download</a>

    <button id="mediaPrev" class="media-nav prev">❮</button>
    <button id="mediaNext" class="media-nav next">❯</button>
</div>
