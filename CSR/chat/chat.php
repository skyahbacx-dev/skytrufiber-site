<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}
$csrUser = $_SESSION["csr_user"];
?>

<div id="chat-container">

    <!-- LEFT PANEL -->
    <div class="chat-left-panel">
        <div class="left-header">
            <h3>Clients</h3>
            <input type="text" id="client-search" placeholder="Search clients...">
        </div>
        <div id="client-list" class="client-list"></div>
    </div>

    <!-- MIDDLE CHAT PANEL -->
    <div class="chat-middle-panel">

        <div class="chat-wrapper">

            <!-- CHAT HEADER -->
            <div class="chat-header">
                <div class="chat-with">
                    <h3 id="chat-client-name">Select a Client</h3>
                    <span id="client-status" class="status-dot offline"></span>
                </div>
                <div id="typing-indicator" class="typing-indicator" style="display:none;">
                    typing...
                </div>
            </div>

            <!-- CHAT MESSAGES -->
            <div id="chat-messages" class="chat-messages"></div>

            <!-- REPLY PREVIEW BAR (Messenger Style) -->
            <div id="reply-bar" class="reply-bar">
                <span id="reply-preview-text" class="reply-content"></span>
                <button id="cancel-reply" class="cancel-reply">&times;</button>
            </div>

            <!-- INPUT AREA -->
            <div class="chat-input-area">

                <!-- MULTIPLE MEDIA SUPPORT -->
                <input type="file" id="chat-upload-media" accept="image/*,video/*,application/pdf" multiple hidden>

                <button id="upload-btn" class="upload-btn">
                    <i class="fa fa-paperclip"></i>
                </button>

                <div class="chat-input-box">
                    <input type="text" id="chat-input" placeholder="Type a message..." autocomplete="off">
                </div>

                <button id="send-btn" class="chat-send-btn">
                    <i class="fa fa-paper-plane"></i>
                </button>

            </div> <!-- chat-input-area -->

        </div> <!-- chat-wrapper -->

    </div> <!-- chat-middle-panel -->


    <!-- RIGHT PANEL -->
    <div class="chat-right-panel">
        <div class="client-info-panel">
            <h3>Client Details</h3>
            <p>Select a client to view details</p>
            <div id="client-info-content"></div>
        </div>
    </div>

</div> <!-- chat-container -->

<input type="hidden" id="csr-username" value="<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>">

<!-- LIGHTBOX VIEWER FOR IMAGES -->
<div id="lightbox-overlay">
    <span id="lightbox-close">&times;</span>
    <img id="lightbox-image">
</div>

<!-- MULTIPLE FILE PREVIEW MODAL -->
<div id="preview-overlay" class="preview-overlay">
    <div class="preview-box">

        <!-- The thumbnails are injected here -->
        <div id="preview-files" class="preview-files"></div>

        <div class="preview-actions">
            <button id="cancel-preview" class="preview-btn cancel">Cancel</button>
            <button id="send-preview" class="preview-btn send">Send</button>
        </div>
    </div>
</div>
