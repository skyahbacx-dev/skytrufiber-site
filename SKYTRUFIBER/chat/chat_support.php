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

<div class="chat-wrapper-outer">

    <div class="chat-box">

        <!-- HEADER -->
        <div class="chat-header">
            <img src="/upload/AHBALOGO.png" class="chat-logo">
            <div>
                <h3>SkyTruFiber Support</h3>
                <span class="chat-status">Support Team Active</span>
            </div>
        </div>

        <!-- MESSAGE AREA -->
        <div id="chat-window" class="chat-window"></div>

        <!-- PREVIEW INLINE -->
        <div id="preview-inline" class="preview-inline">
            <div id="preview-files" class="preview-files"></div>
        </div>

        <!-- INPUT AREA -->
        <div class="chat-input-area">
            <input type="file" id="chat-upload-media" multiple hidden>
            <button id="upload-btn" class="upload-btn">📎</button>

            <input id="message-input" type="text" placeholder="Write a message..." autocomplete="off">

            <button id="send-btn" class="send-btn">Send</button>
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
