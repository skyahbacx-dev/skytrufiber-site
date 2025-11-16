<?php
session_start();

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser     = $_SESSION['csr_user'];
$csrFullName = $_SESSION['csr_fullname'] ?? $csrUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?php echo htmlspecialchars(strtoupper($csrUser)); ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<!-- ========== TOP NAVBAR ========== -->
<div class="topnav">
  <div class="nav-left">
    <img src="upload/AHBALOGO.png" class="nav-logo" alt="Logo">
    <h2>CSR DASHBOARD — <?php echo htmlspecialchars(strtoupper($csrUser)); ?></h2>
  </div>
  <div class="nav-buttons">
    <button class="nav-btn active">💬 CHAT DASHBOARD</button>
    <button class="nav-btn" onclick="window.location.href='my_clients.php'">👥 MY CLIENTS</button>
    <button class="nav-btn" onclick="window.location.href='reminders.php'">⏱ REMINDERS</button>
    <button class="nav-btn" onclick="window.location.href='survey_responses.php'">📄 SURVEY RESPONSE</button>
    <button class="nav-btn" onclick="window.location.href='update_profile.php'">👤 EDIT PROFILE</button>
    <a href="csr_logout.php" class="logout-btn">Logout</a>
  </div>
</div>

<!-- ========== MAIN LAYOUT ========== -->
<div class="layout">

  <!-- ===== LEFT: CLIENT LIST ===== -->
  <section class="client-panel">
    <h3>CLIENTS</h3>
    <input type="text" class="search" id="clientSearch" placeholder="Search clients...">
    <div id="clientList" class="client-list"></div>
  </section>

  <!-- ===== RIGHT: CHAT PANEL ===== -->
  <main class="chat-panel">

    <!-- Chat header -->
    <div class="chat-header">
      <div style="display:flex;align-items:center;gap:10px;">
        <img id="chatAvatar" src="upload/default_avatar.png" class="chat-avatar" alt="Avatar">
        <div>
          <div id="chatName" class="chat-name">Select a client</div>
          <div class="chat-status">
            <span class="status-dot offline" id="statusDot"></span>
            <span id="statusText">---</span>
          </div>
        </div>
      </div>
      <button class="info-btn" id="infoBtn">ⓘ</button>
    </div>

    <!-- Messages -->
    <div id="chatMessages" class="chat-box">
      <p class="placeholder">Select a client to start chatting.</p>
    </div>

    <!-- Upload preview row -->
    <div id="previewRow" class="preview-row" style="display:none;">
      <div id="previewContainer" class="preview-container"></div>
    </div>

    <!-- Input bar -->
    <div class="chat-input">
      <input type="file" id="fileInput" multiple accept="image/*,video/*" hidden>
      <button class="upload-icon" type="button" onclick="document.getElementById('fileInput').click()">📎</button>

      <input type="text" id="messageInput" placeholder="Type a message…" disabled>
      <button id="sendBtn" class="send-btn" disabled>✈</button>
    </div>

  </main>

  <!-- ===== SLIDE-OUT CLIENT INFO ===== -->
  <aside class="client-info-panel" id="clientInfoPanel">
    <button class="close-info" type="button">✖</button>
    <h3>Client Information</h3>
    <p><strong id="infoName"></strong></p>
    <p id="infoEmail"></p>
    <p id="infoDistrict"></p>
    <p id="infoBrgy"></p>
  </aside>

</div>

<script>
let selectedClient = 0;
let mediaFiles = [];

/* === LOAD CLIENTS LIST (expects CSR/client_list.php to echo .client-item divs) === */
function loadClients() {
  $.get("client_list.php", function(html){
    $("#clientList").html(html);

    // Each client item should have data-id and data-name at minimum
    $(".client-item").on("click", function(){
      $(".client-item").removeClass("active");
      $(this).addClass("active");

      selectedClient = $(this).data("id");
      const name     = $(this).data("name")     || "Client";
      const email    = $(this).data("email")    || "";
      const district = $(this).data("district") || "";
      const brgy     = $(this).data("barangay") || "";
      const avatar   = $(this).data("avatar")   || "upload/default_avatar.png";

      $("#chatName").text(name);
      $("#infoName").text(name);
      $("#infoEmail").text(email ? ("Email: " + email) : "");
      $("#infoDistrict").text(district ? ("District: " + district) : "");
      $("#infoBrgy").text(brgy ? ("Barangay: " + brgy) : "");
      $("#chatAvatar").attr("src", avatar);

      $("#messageInput").prop("disabled", false);
      $("#sendBtn").prop("disabled", false);

      loadMessages();
    });
  });
}

/* === LOAD MESSAGES FOR SELECTED CLIENT === */
function loadMessages() {
  if (!selectedClient) return;

  $.get("load_chat_csr.php", {client_id: selectedClient}, function(res){
    let html = "";
    if (!Array.isArray(res) || !res.length) {
      html = "<p class='placeholder'>No messages yet.</p>";
      $("#chatMessages").html(html);
      return;
    }

    res.forEach(m => {
      const side = (m.sender_type === "csr") ? "csr" : "client";
      html += `<div class="msg ${side}">
        <div class="bubble">${m.message ? m.message : ""}</div>`;

      if (m.media_path) {
        if (m.media_type === "image") {
          html += `<img src="../${m.media_path}" class="file-img" />`;
        } else if (m.media_type === "video") {
          html += `<video controls class="file-video"><source src="../${m.media_path}"></video>`;
        }
      }

      html += `<div class="meta">${m.created_at}</div></div>`;
    });

    $("#chatMessages").html(html);
    const box = document.getElementById("chatMessages");
    box.scrollTop = box.scrollHeight;
  }, "json");
}

/* === FILE INPUT CHANGE: PREVIEW MULTIPLE FILES === */
document.getElementById("fileInput").addEventListener("change", function(e){
  const files = Array.from(e.target.files);
  if (!files.length) return;

  mediaFiles = mediaFiles.concat(files);
  const previewRow = document.getElementById("previewRow");
  const previewContainer = document.getElementById("previewContainer");
  previewContainer.innerHTML = "";

  mediaFiles.forEach((file, idx) => {
    const url = URL.createObjectURL(file);
    const isImage = file.type.startsWith("image/");
    const thumb = document.createElement(isImage ? "img" : "video");
    thumb.className = "preview-thumb";
    thumb.src = url;
    if (!isImage) thumb.controls = true;

    const wrap = document.createElement("div");
    wrap.className = "preview-item";

    const remove = document.createElement("span");
    remove.className = "preview-remove";
    remove.textContent = "×";
    remove.onclick = () => {
      mediaFiles.splice(idx,1);
      wrap.remove();
      if (!mediaFiles.length) previewRow.style.display = "none";
    };

    wrap.appendChild(thumb);
    wrap.appendChild(remove);
    previewContainer.appendChild(wrap);
  });

  previewRow.style.display = mediaFiles.length ? "flex" : "none";
});

/* === SEND MESSAGE + MULTIPLE FILES === */
document.getElementById("sendBtn").addEventListener("click", function(){
  if (!selectedClient) return;
  const msgInput = document.getElementById("messageInput");
  const text = msgInput.value.trim();

  if (!text && !mediaFiles.length) return;

  const fd = new FormData();
  fd.append("client_id", selectedClient);
  fd.append("csr_fullname", <?php echo json_encode($csrFullName); ?>);
  fd.append("message", text);

  mediaFiles.forEach((file, i) => {
    fd.append("file" + i, file);
  });

  $.ajax({
    url: "save_chat_csr.php",
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function(resp){
      msgInput.value = "";
      mediaFiles = [];
      document.getElementById("previewContainer").innerHTML = "";
      document.getElementById("previewRow").style.display = "none";
      loadMessages();
    }
  });
});

/* === ENTER TO SEND === */
document.getElementById("messageInput").addEventListener("keydown", function(e){
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    document.getElementById("sendBtn").click();
  }
});

/* === INFO PANEL TOGGLE === */
document.getElementById("infoBtn").addEventListener("click", function(){
  document.getElementById("clientInfoPanel").classList.toggle("active");
});
document.querySelector(".close-info").addEventListener("click", function(){
  document.getElementById("clientInfoPanel").classList.remove("active");
});

/* === AUTO REFRESH CHAT === */
setInterval(loadMessages, 2000);

/* === INIT === */
loadClients();
</script>

</body>
</html>
