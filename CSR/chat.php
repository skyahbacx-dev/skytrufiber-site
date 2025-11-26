<?php

if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}
$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>
<link rel="stylesheet" href="chat.css">

<div id="messenger-layout">

    <!-- LEFT PANEL -->
    <aside id="left-panel">
        <div class="left-header">
            <input type="text" id="searchInput" class="search-clients" placeholder="Search clients..." onkeyup="loadClients(this.value)">
        </div>
        <div id="clientList" class="client-scroll"></div>
    </aside>

    <!-- CENTER CHAT -->
    <main id="chat-panel">

        <header id="chat-header">
            <div class="chat-user-info">
                <img id="chatAvatar" src="upload/default-avatar.png" class="chat-header-avatar">
                <div>
                    <div id="chatName" class="chat-header-name">Select a client</div>
                    <div class="chat-header-status" id="chatStatus">
                        <span id="statusDot" class="status-dot offline"></span> Offline
                    </div>
                </div>
            </div>
            <button id="infoBtn" class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
        </header>

        <section id="chatMessages" class="messages-body"></section>
        <section id="previewArea" class="preview-area"></section>

        <footer id="chat-input-bar">
            <label for="fileInput" class="file-upload-icon"><i class="fa-regular fa-image"></i></label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" class="message-field" placeholder="Type a message…">
            <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
        </footer>
    </main>

    <!-- RIGHT PANEL -->
    <aside id="infoPanel" class="right-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <img src="upload/default-avatar.png" id="infoAvatar" class="info-avatar">
        <h2 id="infoName">Client Name</h2>
        <p id="infoEmail"></p>
        <div><b>District:</b> <span id="infoDistrict"></span></div>
        <div><b>Barangay:</b> <span id="infoBrgy"></span></div>
    </aside>

</div>

<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>
<div id="assignModal" class="modal-bg">
  <div class="modal-box">
    <h3>Assign Client?</h3>
    <input type="hidden" id="assignClientId">
    <button class="modal-ok" onclick="assignClient()">Assign</button>
    <button class="modal-cancel" onclick="$('#assignModal').removeClass('show')">Cancel</button>
  </div>
</div>

<div id="unassignModal" class="modal-bg">
  <div class="modal-box">
    <h3>Unassign Client?</h3>
    <input type="hidden" id="unassignClientId">
    <button class="modal-ok" onclick="unassignClient()">Unassign</button>
    <button class="modal-cancel" onclick="$('#unassignModal').removeClass('show')">Cancel</button>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="csr_chat.js"></script>
