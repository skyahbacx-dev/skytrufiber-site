<?php if (!isset($_SESSION)) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkyTruFiber Support</title>

<link rel="stylesheet" href="chat_support.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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

        <!-- CHAT MESSAGES -->
        <div id="chat-messages" class="chat-messages"></div>

        <!-- INLINE PREVIEW -->
        <div id="preview-inline" class="preview-inline">
            <div id="preview-files" class="preview-files"></div>
        </div>

        <!-- INPUT BAR -->
        <div class="chat-input-area">
            <input type="file" id="chat-upload-media" accept="image/*,video/*,application/pdf" multiple hidden>

            <button id="upload-btn" class="upload-btn">
                <i class="fa fa-paperclip"></i>
            </button>

            <div class="chat-input-box">
                <input type="text" id="message-input" placeholder="Write a message..." autocomplete="off">
            </div>

            <button id="send-btn" class="chat-send-btn">
                <i class="fa fa-paper-plane"></i>
            </button>
        </div>

    </div>
</div>

<!-- SCROLL TO BOTTOM BUTTON -->
<button id="scroll-bottom-btn" class="scroll-bottom-btn">
    <i class="fa fa-arrow-down"></i>
</button>

<!-- LIGHTBOX -->
<div id="lightbox-overlay">
    <span id="lightbox-close">&times;</span>
    <img id="lightbox-image">
</div>

</body>
</html>
