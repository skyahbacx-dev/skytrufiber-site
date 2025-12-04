<?php if (!isset($_SESSION)) session_start(); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkyTruFiber Support</title>

<!-- Prevent caching -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<!-- ICONS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- CSS -->
<link rel="stylesheet" href="chat_support.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- JS -->
<script src="chat_support.js" defer></script>
</head>

<body>

<div id="chat-overlay">
    <div class="chat-modal">

        <!-- =========================
             HEADER
        ========================== -->
        <div class="chat-header">

            <div class="chat-header-left">
                <img src="../../SKYTRUFIBER.png" class="chat-header-logo">
                <div>
                    <h2>SkyTruFiber Support</h2>
                    <span class="status active">Support Team Active</span>
                </div>
            </div>

            <div class="chat-header-right">
                <button id="theme-toggle" class="theme-toggle">
                    <i class="fa-solid fa-moon theme-icon moon-icon"></i>
                    <i class="fa-solid fa-sun theme-icon sun-icon"></i>
                </button>

                <button id="logout-btn" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </div>

        </div>

        <!-- =========================
             CHAT MESSAGES
        ========================== -->
        <div id="chat-messages"></div>

        <button id="scroll-bottom-btn">
            <i class="fa-solid fa-arrow-down"></i>
        </button>

        <!-- =========================
             PREVIEW AREA
        ========================== -->
        <div id="preview-inline" class="preview-inline">
            <div id="preview-close">&times;</div>
            <div id="preview-files"></div>
        </div>

        <!-- =========================
             INPUT BAR
        ========================== -->
        <div class="chat-input-area">

            <input type="file" id="chat-upload-media" multiple hidden>

            <button id="upload-btn">
                <img src="upload_icon.png">
            </button>

            <div class="chat-input-box">
                <input id="message-input" type="text" placeholder="Type a message...">
            </div>

            <button id="send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>

        </div>

    </div> <!-- END CHAT MODAL -->
</div> <!-- END OVERLAY -->


<!-- =========================
     LIGHTBOX OVERLAY
========================== -->
<div id="lightbox-overlay">

    <span id="lightbox-close">&times;</span>

    <img id="lightbox-image" draggable="false">
    <video id="lightbox-video" controls></video>

    <button id="lightbox-prev">&#10094;</button>
    <button id="lightbox-next">&#10095;</button>

    <div id="lightbox-index"></div>

    <a id="lightbox-download" download>
        <i class="fa-solid fa-download"></i>
    </a>
</div>

<!-- IMPORTANT:
The reaction picker and message action popup
are NOT placed here anymore.
They are created dynamically by JS.
-->

</body>
</html>
