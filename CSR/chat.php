<?php

if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?php echo htmlspecialchars($csrUser); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="csr_dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const csrUser = <?php echo json_encode($csrUser); ?>;
    const csrFullname = <?php echo json_encode($csrFullName); ?>;
</script>
<script src="csr_chat.js"></script>
</head>

<body>

<div class="container">

    <!-- ================= LEFT: CLIENT SIDEBAR ================= -->
    <aside class="sidebar">
        <input class="search" placeholder="Search clients..." id="searchInput">

        <!-- CLIENT LIST POPULATES HERE -->
        <div id="clientList" class="client-list"></div>
    </aside>

    <!-- ================= CHAT PANEL ================= -->
    <main class="chat-panel">

        <div class="chat-header">
            <div class="user-section">
                <img id="chatAvatar" src="upload/default-avatar.png" class="chat-avatar">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
                    <div id="chatStatus" class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span>
                        Offline
                    </div>
                </div>
            </div>
            <button class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
        </div>

        <!-- CHAT MESSAGES -->
        <div class="chat-box" id="chatMessages"></div>

        <!-- MEDIA PREVIEW -->
        <div id="previewArea" class="preview-area"></div>

        <!-- INPUT AREA -->
        <div class="chat-input">
            <label class="upload-icon">
                <i class="fa-regular fa-image"></i>
                <input type="file" id="fileInput" multiple style="display:none;">
            </label>

            <input type="text" id="messageInput" placeholder="Type anything.....">

            <button id="sendBtn" class="send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>

    </main>

</div>

<!-- ================= CLIENT INFO SLIDE PANEL ================= -->
<aside id="clientInfoPanel" class="client-info-panel">
    <button class="close-info" onclick="toggleClientInfo()">✖</button>
    <h3>Client Information</h3>
    <p><strong id="infoName"></strong></p>
    <p id="infoEmail"></p>
    <p><b>District:</b> <span id="infoDistrict"></span></p>
    <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
</aside>

<!-- ================= MEDIA VIEWER ================= -->
<div id="mediaModal" class="media-modal">
    <span id="closeMediaModal" class="close-modal">✖</span>
    <img id="mediaModalContent" class="modal-content">
</div>

<script>
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));
</script>

</body>
</html>
