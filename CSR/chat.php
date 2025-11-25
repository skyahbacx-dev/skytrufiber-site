<?php
if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>

<div id="messenger-layout">

    <!-- LEFT PANEL – CLIENT LIST -->
    <aside id="left-panel">
        <div class="left-header">
            <input type="text" id="searchInput" class="search-clients" placeholder="Search clients...">
        </div>

        <div id="clientList" class="client-scroll"></div>
    </aside>

    <!-- CENTER CHAT PANEL -->
    <main id="chat-panel">

        <!-- HEADER -->
        <header id="chat-header">
            <div class="chat-user-info">
                <img id="chatAvatar" src="upload/default-avatar.png" class="chat-header-avatar">
                <div>
                    <div id="chatName" class="chat-header-name">Select a client</div>
                    <div id="chatStatus" class="chat-header-status">
                        <span id="statusDot" class="status-dot offline"></span> Offline
                    </div>
                </div>
            </div>
            <button id="infoBtn" class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
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

    <!-- RIGHT PANEL – CLIENT INFO -->
    <aside id="infoPanel" class="right-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>

        <div class="info-content">
            <img src="upload/default-avatar.png" id="infoAvatar" class="info-avatar">
            <h2 id="infoName">Client Name</h2>
            <p id="infoEmail"></p>
            <div><b>District:</b> <span id="infoDistrict"></span></div>
            <div><b>Barangay:</b> <span id="infoBrgy"></span></div>
        </div>
    </aside>

</div>

<!-- MEDIA VIEWER -->
<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>
