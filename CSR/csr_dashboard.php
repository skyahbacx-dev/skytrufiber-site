<?php
session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csrFullName) ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
<link rel="stylesheet" href="chat.css">    
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const csrUser     = "<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>";
const csrFullname = "<?= htmlspecialchars($csrFullName, ENT_QUOTES) ?>";
</script>
<script src="csr_chat.js"></script>
</head>

<body>

<!-- ============ TOP HEADER ============ -->
<div class="topnav">
    <h2>CSR DASHBOARD — <?= strtoupper($csrUser) ?></h2>
    <a href="csr_logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- ============ MAIN VIEW ============ -->
<div class="layout">

    <!-- LEFT CLIENT SIDEBAR -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input class="search" id="searchInput" placeholder="Search clients...">
        <div id="clientList" class="client-list"></div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">

        <!-- CHAT HEADER -->
        <div class="chat-header">
            <div class="header-left">
                <div class="header-avatar"></div>
                <div>
                    <div id="chatName" class="header-name">Select a client</div>
                    <div id="chatStatus" class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span> Offline
                    </div>
                </div>
            </div>
            <button class="info-btn" onclick="toggleClientInfo()"><i class="fa-solid fa-circle-info"></i></button>
        </div>

        <!-- CHAT MESSAGES -->
        <div class="chat-box" id="chatMessages"></div>

        <!-- PREVIEW STRIP -->
        <div id="previewArea" class="preview-area"></div>

        <!-- INPUT BAR -->
        <div class="chat-input">
            <label for="fileInput" class="upload-icon"><i class="fa-regular fa-image"></i></label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="type anything.....">
            <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
        </div>

    </div>

    <!-- RIGHT SLIDE INFO PANEL -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <h3>Clients Information</h3>
        <p><strong id="infoName"></strong></p>
        <p id="infoEmail"></p>
        <p><b>District:</b> <span id="infoDistrict"></span></p>
        <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
    </aside>

</div>

<!-- FULL MEDIA POPUP -->
<div id="mediaModal" class="media-modal">
    <span id="closeMediaModal" class="close-modal">✖</span>
    <img id="mediaModalContent" class="modal-content">
</div>

</body>
</html>
