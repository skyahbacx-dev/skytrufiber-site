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

            <!-- SCROLL BUTTON -->
            <button id="scroll-bottom-btn" class="scroll-bottom-btn">
                <i class="fa fa-arrow-down"></i>
            </button>

            <!-- MEDIA PREVIEW BAR -->
            <div id="preview-inline" class="preview-inline">
                <div id="preview-files" class="preview-files"></div>
            </div>

            <!-- INPUT AREA -->
            <div class="chat-input-area">

                <input type="file" id="chat-upload-media"
                       accept="image/*,video/*,application/pdf" multiple hidden>

                <button id="upload-btn" class="upload-btn">
                    <i class="fa fa-paperclip"></i>
                </button>

                <div class="chat-input-box">
                    <input type="text" id="chat-input"
                           placeholder="Type a message..."
                           autocomplete="off">
                </div>

                <button id="send-btn" class="chat-send-btn">
                    <i class="fa fa-paper-plane"></i>
                </button>

            </div>

        </div> <!-- /chat-wrapper -->

        <!-- ACTION MENU POPUP -->
        <div id="msg-action-popup" class="msg-action-popup">
            <button class="action-edit"><i class="fa fa-pen"></i> Edit</button>
            <button class="action-unsend"><i class="fa fa-ban"></i> Unsend</button>
            <button class="action-delete"><i class="fa fa-trash"></i> Delete</button>
            <button class="action-cancel">Cancel</button>
        </div>

    </div> <!-- /chat-middle-panel -->

    <!-- RIGHT PANEL -->
    <div class="chat-right-panel">
        <div class="client-info-panel">
            <h3>Client Details</h3>
            <p>Select a client to view details</p>
            <div id="client-info-content"></div>
        </div>
    </div>

</div> <!-- /chat-container -->


<!-- Hidden: Logged in CSR username -->
<input type="hidden" id="csr-username" value="<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>">


<!-- =============================
     GLOBAL LIGHTBOX (updated)
============================= -->
<div id="lightbox-overlay">

    <!-- Close X -->
    <span id="lightbox-close">&times;</span>

    <!-- MAIN CENTERED VIEW -->
    <div id="lightbox-main-wrapper">

        <button id="lightbox-prev" class="lb-arrow">&#10094;</button>

        <img id="lightbox-image" class="lb-media">
        <video id="lightbox-video" class="lb-media" controls></video>

        <button id="lightbox-next" class="lb-arrow">&#10095;</button>
    </div>

    <!-- DOWNLOAD BUTTON -->
    <a id="lightbox-download" class="lightbox-download" download>
        <i class="fa fa-download"></i>
    </a>

    <!-- THUMBNAIL BAR -->
    <div id="lightbox-thumbs"></div>

</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
