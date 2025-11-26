<?php

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? "CSR";
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="chat.css?v=<?php echo time(); ?>">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<div id="messenger-layout">

    <!-- LEFT PANEL -->
    <aside id="left-panel">
        <input type="text" id="searchInput" class="search-clients" placeholder="Search clients..." onkeyup="loadClientList()">
        <div id="clientList" class="client-scroll"></div>
    </aside>

    <!-- CHAT PANEL -->
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

        <!-- INPUT BAR -->
        <footer id="chat-input-bar">
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" class="message-field" placeholder="Type a message…" disabled>
            <button id="sendBtn" class="send-btn" disabled><i class="fa-solid fa-paper-plane"></i></button>
        </footer>

    </main>

    <!-- RIGHT PANEL -->
    <aside id="infoPanel" class="right-panel" style="display:none;">

        <button class="close-info" onclick="toggleClientInfo()">✖</button>

        <div class="info-content">
            <img src="upload/default-avatar.png" id="infoAvatar" class="info-avatar">
            <h2 id="infoName">Client Name</h2>
            <p id="infoEmail"></p>
            <div><b>District:</b> <span id="infoDistrict"></span></div>
            <div><b>Barangay:</b> <span id="infoBrgy"></span></div>

            <div id="assignControls" style="margin-top:15px; text-align:center;">
                <button id="assignBtn" class="assign-btn" onclick="assignSelected()">➕ Assign</button>
                <button id="unassignBtn" class="unassign-btn" onclick="unassignSelected()">➖ Unassign</button>
                <span id="lockedIcon" class="lock-icon">🔒 Assigned to another CSR</span>
            </div>
        </div>

    </aside>

</div>

<script>
var csrUser = "<?php echo $csrUser; ?>";
</script>
<script src="client_support.js?v=<?php echo time(); ?>"></script>

</body>
</html>
