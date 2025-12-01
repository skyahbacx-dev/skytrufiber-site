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

<div id="chat-overlay">
    <div class="chat-modal">

        <!-- HEADER -->
        <div class="chat-header">
            <div class="chat-header-left">
                <img src="../../SKYTRUFIBER.png" class="chat-header-logo">
                <div>
                    <h2>SkyTruFiber Support</h2>
                    <span class="status active">Support Team Active</span>
                </div>
            </div>
        </div>

        <!-- CHAT MESSAGES -->
        <div id="chat-messages" class="chat-messages"></div>

        <!-- PREVIEW INLINE MEDIA -->
        <div id="preview-inline" class="preview-inline">
            <div id="preview-files" class="preview-files"></div>
        </div>

        <!-- INPUT BAR -->
        <div class="chat-input-area">
            <input type="file" id="chat-upload-media" multiple hidden>

            <button id="upload-btn" class="upload-btn">📎</button>

            <div class="chat-input-box">
                <input id="message-input" type="text" placeholder="Type a message...">
            </div>

            <button id="send-btn" class="chat-send-btn">
                <i class="fa fa-paper-plane"></i>
            </button>
        </div>

        <button id="scroll-bottom-btn" class="scroll-bottom-btn">
            <i class="fa fa-arrow-down"></i>
        </button>

    </div>
</div>

<!-- LIGHTBOX -->
<div id="lightbox-overlay">
    <span id="lightbox-close">&times;</span>

    <img id="lightbox-image" style="display:none;">
    <video id="lightbox-video" controls style="display:none; max-width:90%; max-height:90%; border-radius:14px;"></video>

    <button id="lightbox-prev" class="lightbox-nav">&#10094;</button>
    <button id="lightbox-next" class="lightbox-nav">&#10095;</button>

    <a id="lightbox-download" href="#" download class="lightbox-download">⬇ Download</a>
</div>


</body>
</html>
