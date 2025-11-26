<?php
session_start();
if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>

<div id="messenger-layout">

    <!-- LEFT SIDEBAR -->
    <aside id="left-panel">
        <input type="text" id="searchInput" placeholder="Search clients..." class="search-clients">
        <div id="clientList" class="client-scroll"></div>
    </aside>

    <!-- CHAT PANEL -->
    <main id="chat-panel">

        <!-- HEADER -->
        <header id="chat-header">
            <div class="header-user">
                <img id="chatAvatar" src="upload/default-avatar.png" class="header-avatar">
                <div class="header-meta">
                    <div id="chatName" class="chat-header-name">Select a client</div>
                    <div id="chatStatus" class="chat-header-status">
                        <span class="status-dot offline" id="statusDot"></span> Offline
                    </div>
                </div>
            </div>
            <button id="infoBtn" class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
        </header>

        <!-- MESSAGES -->
        <section id="chatMessages" class="messages-body"></section>

        <!-- MEDIA PREVIEW -->
        <section id="previewArea" class="preview-area"></section>

        <!-- MESSAGE INPUT -->
        <footer id="chat-input-bar">
            <label for="fileInput" class="file-upload-icon">
                <i class="fa-regular fa-image"></i>
            </label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="Type a message…" class="message-field">
            <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
        </footer>

    </main>

    <!-- RIGHT USER INFO PANEL -->
    <aside id="infoPanel" class="right-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>

        <img src="upload/default-avatar.png" id="infoAvatar" class="info-avatar">

        <h2 id="infoName" class="info-name">Client Name</h2>
        <p id="infoEmail"></p>
        <p><b>District:</b> <span id="infoDistrict"></span></p>
        <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
    </aside>

</div>

<!-- MEDIA VIEWER -->
<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>
