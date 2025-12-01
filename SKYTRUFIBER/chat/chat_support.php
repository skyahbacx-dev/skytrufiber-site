<?php if (!isset($_SESSION)) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkyTruFiber Support</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
      integrity="sha512-6mTfEtp+uWz+t0T1Ax7P0GDaXJ5mZLAFkmWg0ctV1LZC3L6kCixYJmTu9Ql73fX4zUPeCGD6qq9PzLSRGu6Y7g=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer" />

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

        <!-- PREVIEW INLINE -->
        <div id="preview-inline" class="preview-inline">

            <!-- close button for entire preview bar -->
            <div id="preview-close" title="Remove all previews">&times;</div>

            <!-- container for preview thumbnails -->
            <div id="preview-files" class="preview-files"></div>

        </div>

        <!-- INPUT BAR -->
        <div class="chat-input-area">

            <input type="file" id="chat-upload-media" multiple hidden>

            <button id="upload-btn" class="upload-btn">
                <i class="fa-solid fa-paperclip"></i>
            </button>

            <div class="chat-input-box">
                <input id="message-input" type="text" placeholder="Type a message...">
            </div>

            <button id="send-btn" class="chat-send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>

        </div>

        <!-- SCROLL DOWN BUTTON -->
        <button id="scroll-bottom-btn" class="scroll-bottom-btn">
            <i class="fa-solid fa-arrow-down"></i>
        </button>

    </div>
</div>

<!-- LIGHTBOX -->
<div id="lightbox-overlay">

    <span id="lightbox-close">&times;</span>

    <img id="lightbox-image" style="display:none;">
    <video id="lightbox-video" controls style="display:none; border-radius:14px;"></video>

    <button id="lightbox-prev" class="lightbox-nav">&#10094;</button>
    <button id="lightbox-next" class="lightbox-nav">&#10095;</button>

    <a id="lightbox-download" href="#" download class="lightbox-download">
        <i class="fa-solid fa-download"></i> Download
    </a>

</div>

</body>
</html>
