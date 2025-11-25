<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["user"])) {
    header("Location: skytrufiber.php");
    exit;
}

$user_id = $_SESSION["user"];
$full_name = $_SESSION["name"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support Chat</title>

<link rel="stylesheet" href="support_chat.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>

<div id="support-layout">

    <header id="support-header">
        <div class="user-info-area">
            <img src="../AHBALOGO.png" class="client-avatar-header">
            <div>
                <h2><?php echo strtoupper($full_name); ?></h2>
                <span>Connected to CSR Support</span>
            </div>
        </div>
    </header>

    <section id="chatMessages" class="messages-body"></section>

    <section id="previewArea" class="preview-area"></section>

    <footer id="chat-input-bar">
        <label for="fileInput" class="file-upload-icon">
            <i class="fa-regular fa-image"></i>
        </label>
        <input type="file" id="fileInput" multiple style="display:none;">
        <input type="text" id="messageInput" class="message-field" placeholder="Type a message…">
        <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
    </footer>

</div>

<script src="support_chat.js"></script>

<script>
const username = "<?php echo htmlspecialchars($full_name, ENT_QUOTES); ?>";
const userId  = "<?php echo $user_id; ?>";
</script>

</body>
</html>
