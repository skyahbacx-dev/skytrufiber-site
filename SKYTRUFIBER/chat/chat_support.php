<?php if (!isset($_SESSION)) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkyTruFiber Support</title>

<link rel="stylesheet" href="chat_support.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="chat_support.js"></script>

</head>
<body>

<div id="support-overlay">
    <div class="chat-modal">

        <!-- HEADER -->
        <div class="chat-header">
            <div class="chat-header-left">
                <img src="/upload/AHBALOGO.png" class="chat-header-logo">
                <div>
                    <h2>SkyTruFiber Support</h2>
                    <span class="status active">Support Team Active</span>
                </div>
            </div>
        </div>

        <!-- MESSAGE WINDOW -->
        <div id="chat-window" class="chat-window"></div>

        <!-- PREVIEW INLINE -->
        <div id="preview-inline" class="preview-inline">
            <div id="preview-files" class="preview-files"></div>
        </div>

        <!-- INPUT -->
        <div class="chat-input-bar">
            <input type="file" id="chat-upload-media" multiple hidden>
            <button id="upload-btn" class="upload-btn">📎</button>

            <input type="text" id="message-input" placeholder="Write a message..." autocomplete="off">

            <button id="send-btn" class="send-btn">
                <i class="fa fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<!-- LIGHTBOX -->
<div id="lightbox-overlay">
    <span id="lightbox-close">&times;</span>
    <img id="lightbox-image">
</div>

</body>
</html>
