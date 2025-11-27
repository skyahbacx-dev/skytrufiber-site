<?php
session_start();
require_once "../../db_connect.php";

// If user not logged in redirect to login
if (!isset($_SESSION["client_id"])) {
    header("Location: ../login.php");
    exit;
}

$clientID   = $_SESSION["client_id"];
$clientName = $_SESSION["client_name"] ?? "Guest";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support Chat</title>

<link rel="stylesheet" href="chat_support.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
const clientID   = "<?= $clientID ?>";
const clientName = "<?= htmlspecialchars($clientName, ENT_QUOTES) ?>";
</script>

<script src="chat_support.js"></script>

</head>
<body>

<div class="support-wrapper">

    <!-- CHAT WINDOW CARD -->
    <div class="support-card">

        <!-- HEADER -->
        <div class="support-header">
            <img src="../../AHBALOGO.png" class="support-logo">
            <div>
                <h3>SkyTruFiber Support</h3>
                <small id="csr-status">Support Team Active</small>
            </div>
        </div>

        <!-- MESSAGES AREA -->
        <div id="support-messages"></div>

        <!-- TYPING INDICATOR -->
        <div id="typing-indicator" style="display:none;">CSR is typing...</div>

        <!-- INPUT AREA -->
        <div class="support-input">
            <input type="text" id="message-box" placeholder="Write a message..." autocomplete="off">

            <button class="upload-btn" onclick="triggerUpload()">
                <i class="fa-solid fa-paperclip"></i>
            </button>

            <input type="file" id="media-upload" accept="image/*,video/*" style="display:none;" onchange="uploadMedia()">

            <button class="send-btn" onclick="sendMessage()">Send</button>
        </div>

    </div>
</div>

</body>
</html>
