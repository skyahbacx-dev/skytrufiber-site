<?php
if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Chat — SkyTruFiber</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="chat.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div id="messenger-layout">

    <!-- LEFT CLIENT LIST -->
    <aside id="left-panel">
        <input type="text" id="searchInput" class="search-input" placeholder="Search clients…">
        <div id="clientList" class="client-scroll"></div>
    </aside>

    <!-- CHAT WINDOW -->
    <main id="chat-panel">

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

        <section id="chatMessages" class="messages-body"></section>

        <section id="previewArea" class="preview-area"></section>

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

    <!-- RIGHT PANEL -->
    <aside id="infoPanel" class="right-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>

        <div class="info-content">
            <img src="upload/default-avatar.png" id="infoAvatar" class="info-avatar">
            <h2 id="infoName">Client Name</h2>
            <p id="infoEmail"></p>
            <p><b>District:</b> <span id="infoDistrict"></span></p>
            <p><b>Barangay:</b> <span id="infoBrgy"></span></p>

            <hr>

            <div id="assignContainer" class="assign-box">
                <p id="assignLabel">Assign this client?</p>
                <button id="assignBtn" class="assign-btn yes">Assign</button>
                <button id="unassignBtn" class="assign-btn no">Unassign</button>
            </div>
        </div>
    </aside>
</div>

<!-- Media Modal -->
<div id="mediaModal" class="media-viewer">
    <span id="closeMediaModal" class="media-close">✖</span>
    <img id="mediaModalContent" class="media-content">
</div>
<div id="mediaModal" onclick="this.style.display='none'">
  <img id="mediaViewer">
</div>

<script>
function openMedia(src){
    document.getElementById("mediaViewer").src = src;
    document.getElementById("mediaModal").style.display = "flex";
}
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="csr_chat.js"></script>
</body>
</html>
