<?php
session_start();
if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>

<div class="chat-layout">

    <!-- LEFT: CLIENT SIDEBAR -->
    <aside class="client-sidebar">
        <input type="text" id="searchInput" class="client-search" placeholder="Search clients...">
        <div id="clientList" class="client-list"></div>
    </aside>

    <!-- CENTER: CHAT PANEL -->
    <section class="chat-panel">

        <!-- HEADER -->
        <header class="chat-header">
            <div class="chat-user">
                <img id="chatAvatar" src="upload/default-avatar.png" class="chat-avatar">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
                    <div id="chatStatus" class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span> Offline
                    </div>
                </div>
            </div>

            <button id="infoToggle" class="info-toggle-btn">ⓘ</button>
        </header>

        <!-- MESSAGES -->
        <div id="chatMessages" class="chat-box"></div>

        <!-- PREVIEW QUEUE -->
        <div id="previewArea" class="preview-area"></div>

        <!-- INPUT BAR -->
        <footer class="chat-input">
            <label for="fileInput" class="upload-icon">
                <i class="fa-regular fa-image"></i>
            </label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="Type anything.....">
            <button id="sendBtn" class="send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </footer>

    </section>

    <!-- RIGHT: INFO PANEL (SLIDE-PUSH) -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <h3>Client Information</h3>
        <p><strong id="infoName"></strong></p>
        <p id="infoEmail"></p>
        <p><b>District:</b> <span id="infoDistrict"></span></p>
        <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
    </aside>

</div>

<!-- MEDIA VIEWER -->
<div id="mediaModal" class="media-modal">
    <span id="closeMediaModal" class="close-modal">✖</span>
    <img id="mediaModalContent" class="modal-content">
</div>
