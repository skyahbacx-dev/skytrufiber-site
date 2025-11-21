<?php
// chat.php - Chat UI Section Only
?>

<!-- CLIENT + CHAT WRAPPER -->
<div class="chat-wrapper">

    <!-- LEFT CLIENT LIST -->
    <div class="client-panel">
        <input class="search" placeholder="Search clients..." id="searchInput">
        <div id="clientList" class="client-list"></div>
    </div>

    <!-- CHAT PANEL CENTER -->
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

            <button class="info-btn" id="toggleInfoBtn">ⓘ</button>
        </div>

        <!-- CLIENT INFO COLLAPSIBLE -->
        <div class="client-info-inline" id="clientInfoInline">
            <h3>Client Information</h3>
            <p><strong id="infoName"></strong></p>
            <p id="infoEmail"></p>
            <p><b>District:</b> <span id="infoDistrict"></span></p>
            <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
        </div>

        <!-- CHAT MESSAGES -->
        <div class="chat-box" id="chatMessages"></div>

        <!-- PREVIEW AREA -->
        <div id="previewArea" class="preview-area"></div>

        <!-- INPUT BAR -->
        <div class="chat-input">
            <label for="fileInput" class="upload-icon">
                <i class="fa-regular fa-image"></i>
            </label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="Type anything.....">
            <button id="sendBtn" class="send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>

    </div>
</div>
