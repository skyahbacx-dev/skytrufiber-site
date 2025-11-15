<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

$stmt = $conn->prepare("SELECT full_name, profile_pic FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $data['full_name'] ?? $csr_user;
$csr_avatar   = $data['profile_pic'] ?? 'CSR/default_avatar.png';

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    if ($_GET['ajax'] === 'load_clients') {
        $tab = $_GET['tab'] ?? 'all';

        if ($tab === 'mine') {
            $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY name ASC");
            $stmt->execute([':csr' => $csr_user]);
        } else {
            $stmt = $conn->query("SELECT * FROM clients ORDER BY name ASC");
        }

        $rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $last = $r['last_active'] ?? null;
            $status = ($last && strtotime($last) > time() - 60) ? 'Online' : 'Offline';
            $r['status'] = $status;
            $rows[] = $r;
        }
        echo json_encode($rows);
        exit;
    }

    if ($_GET['ajax'] === 'get_client' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['ajax'] === 'assign' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id AND (assigned_csr IS NULL OR assigned_csr = '')");
        $stmt->execute([':csr' => $csr_user, ':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($_GET['ajax'] === 'unassign' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id AND assigned_csr = :csr");
        $stmt->execute([':id' => $id, ':csr' => $csr_user]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['error' => 'bad request']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=22">
</head>
<body>

<header class="topnav">
  <img src="AHBALOGO.png" class="nav-logo">
  <h2>CSR DASHBOARD — CSR ONE</h2>

  <nav class="nav-buttons">
    <button class="nav-btn active" onclick="switchTab(this,'all')">💬 CHAT DASHBOARD</button>
    <button class="nav-btn" onclick="switchTab(this,'mine')">👤 MY CLIENTS</button>
    <button class="nav-btn" onclick="window.location.href='reminders.php'">⏱ REMINDERS</button>
    <button class="nav-btn" onclick="window.location.href='survey_responses.php'">📑 SURVEY RESPONSE</button>
    <button class="nav-btn" onclick="window.location.href='update_profile.php'">👤 EDIT PROFILE</button>
  </nav>

  <a href="csr_logout.php" class="logout-btn">Logout</a>
</header>

<div class="layout">

<!-- LEFT CLIENT PANEL -->
<section class="client-panel">
  <h3>CLIENTS</h3>
  <input class="search" placeholder="Search clients...">
  <div id="clientList" class="client-list"></div>
</section>

<!-- CHAT PANEL -->
<main class="chat-panel">
  <div class="chat-header">
    <img id="chatAvatar" src="CSR/lion.PNG" class="chat-avatar">
    <div>
      <div id="chatName" class="chat-name">Select a client</div>
      <div class="chat-status">
        <span id="statusDot" class="status-dot offline"></span>
        <span id="chatStatus">---</span>
      </div>
    </div>
    <button id="infoBtn" class="info-btn">ⓘ</button>
  </div>

  <div id="chatBox" class="chat-box"><p class="placeholder">Select a client to start chatting.</p></div>

  <div id="uploadPreview" class="photo-preview-group" style="display:none;">
    <div class="photo-item"><span class="remove-photo">✖</span><img id="previewImg" src=""></div>
  </div>

  <div id="chatInput" class="chat-input disabled">
    <label for="fileUpload" class="upload-icon">🖼</label>
    <input type="file" id="fileUpload" style="display:none">
    <input type="text" id="msg" placeholder="type anything....." disabled>
    <button id="sendBtn" class="send-btn" disabled>✈</button>
  </div>
</main>

<!-- SLIDE PANEL -->
<aside id="clientInfoPanel" class="client-info-panel">
  <button class="close-info">✖</button>
  <h3>Clients Information</h3>
  <p><strong id="infoName"></strong></p>
  <p id="infoEmail"></p>
  <p>District:</p><p id="infoDistrict"></p>
  <p>Barangay:</p><p id="infoBrgy"></p>
</aside>

</div>

<script>
// ---- SAME JS AS YOUR CURRENT FILE WITH UI ENHANCEMENTS ADDED ----
// (Status dot)
function openClient(id, name){
  currentClient = id;
  document.getElementById('chatName').innerText = name;

  const avatar = (name && name[0].toUpperCase() <= 'M') ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
  document.getElementById('chatAvatar').src = avatar;

  fetch(`/CSR/csr_dashboard.php?ajax=get_client&id=${id}`)
    .then(r=>r.json())
    .then(c=>{
      canChat = (!c.assigned_csr || c.assigned_csr === "<?= $csr_user ?>");

      document.getElementById("chatStatus").innerText =
        !c.assigned_csr ? "Unassigned — you can claim this client." :
        c.assigned_csr === "<?= $csr_user ?>" ? "Assigned to you" :
        "Assigned to CSR: " + c.assigned_csr;

      document.getElementById("statusDot").className =
        "status-dot " + (c.status === "Online" ? "online" : "offline");

      document.getElementById("infoName").innerText = c.name;
      document.getElementById("infoEmail").innerText = c.email;
      document.getElementById("infoDistrict").innerText = c.district;
      document.getElementById("infoBrgy").innerText = c.barangay;

      document.getElementById('chatInput').classList.toggle('disabled', !canChat);
      document.getElementById('msg').disabled = !canChat;
      document.getElementById('sendBtn').disabled = !canChat;

      loadChat();
      if(refreshTimer) clearInterval(refreshTimer);
      refreshTimer = setInterval(loadChat, 3000);
    });
}

loadClients();
</script>

</body>
</html>
