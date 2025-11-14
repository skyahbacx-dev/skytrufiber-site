<?php
session_start();
if (!isset($_SESSION['csr_id'])) { header('Location: csr_login.php'); exit; }
$csr_id = $_SESSION['csr_id'];
$csr_user = $_SESSION['csr_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CSR Dashboard - CSR ONE</title>
<link rel="stylesheet" href="csr_dashboard.css" />
</head>
<body>

<header>
 <div class="menu-btn">☰</div>
 <h2>CSR DASHBOARD - CSR ONE</h2>
 <button onclick="location.href='csr_logout.php'">Logout</button>
</header>

<div class="top-nav">
 <button onclick="location.href='csr_dashboard.php'">CHAT DASHBOARD</button>
 <button onclick="location.href='client_list_page.php'">MY CLIENTS</button>
 <button onclick="location.href='send_reminders.php'">REMINDERS</button>
 <button onclick="location.href='survey_responses.php'">SURVEY RESPONSE</button>
 <button onclick="location.href='update_profile.php'">EDIT PROFILE</button>
</div>

<div class="layout">
 <!-- SIDEBAR CLIENT LIST -->
 <div class="sidebar" id="clientList">
   <h3>CLIENTS</h3>
   <input type="text" placeholder="Search..." id="searchBox" />
   <div id="clientContainer">Loading...</div>
 </div>

 <!-- CHAT AREA -->
 <div class="chat-area">
  <div class="chat-header" id="chatHeader">Select a client</div>
  <div class="messages" id="chatMessages"></div>
  <div class="chat-input">
   <input type="text" id="messageBox" placeholder="type anything..." onkeyup="typing()" />
   <button onclick="sendMessage()">➤</button>
  </div>
 </div>
</div>

<script>
let currentClient = null;
let typingTimer;
let autoRefreshInterval = null;

// Load Client List
function loadClients() {
 fetch('client_list.php')
  .then(res => res.text())
  .then(html => { document.getElementById('clientContainer').innerHTML = html; });
}
loadClients();

// Highlight Client
function highlightClient(id) {
  document.querySelectorAll('.client-item').forEach(e => e.classList.remove('active'));
  let target = document.getElementById('client_' + id);
  if (target) target.classList.add('active');
}

// Remove unread badge
function updateUnread(id) {
 let badge = document.getElementById('badge_' + id);
 if (badge) badge.remove();
}

// Select Client
function openChat(id, name) {
 currentClient = id;
 document.getElementById('chatHeader').innerHTML = name;
 highlightClient(id);
 updateUnread(id);
 loadChat();
 startAutoRefresh();
}

// Load Chat
function loadChat() {
 if (!currentClient) return;
 fetch(`load_chat.php?client_id=${currentClient}`)
  .then(res => res.text())
  .then(html => {
   document.getElementById('chatMessages').innerHTML = html;
   document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
  });
}

// Auto Refresh
function startAutoRefresh() {
 if (autoRefreshInterval) clearInterval(autoRefreshInterval);
 autoRefreshInterval = setInterval(() => { loadChat(); loadClients(); }, 3000);
}

// Send Message
function sendMessage() {
 const msg = document.getElementById('messageBox').value;
 if (!msg.trim() || !currentClient) return;

 fetch('save_chat.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: `client_id=${currentClient}&message=${encodeURIComponent(msg)}`
 })
 .then(() => {
  document.getElementById('messageBox').value='';
  loadChat();
  loadClients();
 });
}

// Typing
function typing() {
 if (!currentClient) return;
 clearTimeout(typingTimer);
 fetch(`typing_status.php?client_id=${currentClient}&status=1`);
 typingTimer = setTimeout(() => {
  fetch(`typing_status.php?client_id=${currentClient}&status=0`);
 }, 1500);
}

/* === DARK MODE === */
function toggleDarkMode() {
 document.body.classList.toggle('dark-mode');
 localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

document.addEventListener('DOMContentLoaded', () => {
 if (localStorage.getItem('darkMode') === 'true') {
   document.body.classList.add('dark-mode');
 }
});

/* === SIDEBAR COLLAPSE === */
document.querySelector('.menu-btn').onclick = () => {
 document.querySelector('.sidebar').classList.toggle('collapsed');
};

</script>

</body>
</html>
