<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}
$csrUser = $_SESSION["csr_user"];
?>

<div id="chat-container">

    <!-- LEFT PANEL — CLIENT LIST -->
    <div class="chat-left-panel">
        <div class="left-header">
            <h3>Clients</h3>
            <input type="text" id="client-search" placeholder="Search clients...">
        </div>
        <div id="client-list" class="client-list"></div>
    </div>

    <!-- MIDDLE PANEL — CHAT WINDOW -->
    <div class="chat-middle-panel">

        <!-- CHAT HEADER -->
        <div class="chat-header">
            <div class="chat-with">
                <h3 id="chat-client-name">Select a Client</h3>
                <span id="client-status" class="status-dot offline"></span>
            </div>
            <div id="typing-indicator" class="typing-indicator" style="display:none;">typing...</div>
        </div>

        <!-- CHAT MESSAGES -->
        <div id="chat-messages" class="chat-messages">
            <!-- Messages dynamically loaded here by chat.js -->
        </div>

        <!-- INPUT AREA -->
        <div class="chat-input-area">
            <input type="file" id="chat-upload-media" accept="image/*,video/*,application/pdf" hidden />

            <button id="upload-btn" class="upload-btn">
                <i class="fa fa-paperclip"></i>
            </button>

            <div class="chat-input-box">
                <input type="text" id="chat-input" placeholder="Type a message..." autocomplete="off">
            </div>

            <button id="send-btn" class="send-btn">
                <i class="fa fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <!-- RIGHT PANEL — CLIENT DETAILS -->
    <div class="chat-right-panel">
        <div id="client-profile">
            <div id="client-info-panel" class="client-info-panel">
                <h3>Client Details</h3>
                <p>Select a client to view details</p>
                <div id="client-info-content"></div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csr-username" value="<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>">
