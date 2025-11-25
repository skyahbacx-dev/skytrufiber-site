<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['client'] ?? $_GET['username'] ?? 'Guest';
date_default_timezone_set("Asia/Manila");

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>SkyTruFiber — Support Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="support_chat.css">
</head>

<body>

<div class="chat-wrap">

  <div class="chat-header">
    <img src="../SKYTRUFIBER.png">
    <div>
      <div class="head-title">SkyTruFiber Support</div>
      <div class="head-sub">Support Team Active</div>
    </div>
  </div>

  <div id="chatBox" class="messages"></div>

  <div class="input-bar">
    <input id="message" placeholder="Write a message…" autocomplete="off"/>
    <input type="file" id="fileUpload" accept="image/*,video/*" style="display:none;">
    <button onclick="document.getElementById('fileUpload').click()">📎</button>
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
const USERNAME = <?= json_encode($username) ?>;
</script>

<script src="client_chat.js"></script>

</body>
</html>
