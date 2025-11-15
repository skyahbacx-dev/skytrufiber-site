<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];
$csr_fullname = $_SESSION['csr_fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard</title>
<link rel="stylesheet" href="csr_dashboard.css?v=22">
</head>
<body>

<div class="top-bar">
    <div class="logo-wrap">
        <img src="SKYTRUFIBER.png" class="logo">
        <span class="title">CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
    <a href="csr_logout.php" class="logout-btn">Logout</a>
</div>

<div class="main-wrap">

    <!-- CLIENT LIST -->
    <div class="client-list" id="clientList"></div>

    <!-- CHAT AREA -->
    <div class="chat-area">
        <div id="chatHeader" class="chat-header">Select a client to start chatting.</div>
        <div id="chatBox" class="messages"></div>

        <div class="send-wrap">
            <label class="upload-btn">
                <input type="file" id="fileUpload" accept="image/*">
                📷
            </label>
            <input type="text" id="messageInput" placeholder="Type your message...">
            <button id="sendBtn">Send</button>
        </div>
    </div>

</div>

<script>
let currentClientID = null;

function loadClients() {
    fetch("csr_dashboard.php?ajax=load_clients")
        .then(res => res.text())
        .then(html => {
            document.getElementById("clientList").innerHTML = html;
        });
}

function selectClient(id, name) {
    currentClientID = id;
    document.getElementById("chatHeader").innerText = name;

    loadChat();
}

function loadChat() {
    if (!currentClientID) return;

    fetch("load_chat.php?client_id=" + currentClientID + "&viewer=csr")
        .then(res => res.json())
        .then(list => {
            const box = document.getElementById("chatBox");
            box.innerHTML = "";

            list.forEach(msg => {
                let bubble = document.createElement("div");
                bubble.className = msg.sender_type === "csr" ? "msg-out" : "msg-in";

                if (msg.file_path) {
                    bubble.innerHTML = `<img src="${msg.file_path}" class="chat-image">`;
                }
                if (msg.message) {
                    bubble.innerHTML += `<p>${msg.message}</p>`;
                }

                box.appendChild(bubble);
            });

            box.scrollTop = box.scrollHeight;
        });
}

document.getElementById("sendBtn").addEventListener("click", () => {
    sendMessage();
});

function sendMessage() {
    if (!currentClientID) return;
    const text = document.getElementById("messageInput").value.trim();
    const file = document.getElementById("fileUpload").files[0];

    let formData = new FormData();
    formData.append("sender_type", "csr");
    formData.append("client_id", currentClientID);
    formData.append("message", text);
    formData.append("csr_user", "<?= $csr_user ?>");
    formData.append("csr_fullname", "<?= $csr_fullname ?>");

    if (file) {
        formData.append("file", file);
    }

    fetch("save_chat.php", { method: "POST", body: formData })
        .then(() => {
            document.getElementById("messageInput").value = "";
            document.getElementById("fileUpload").value = "";
            loadChat();
        });
}

setInterval(loadChat, 1500);
setInterval(loadClients, 3000);
loadClients();
</script>

</body>
</html>
