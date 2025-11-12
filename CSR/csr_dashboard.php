<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// load CSR display name if available
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $r['full_name'] ?? $csr_user;

// logo detection
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : (file_exists('../SKYTRUFIBER/AHBALOGO.png') ? '../SKYTRUFIBER/AHBALOGO.png' : '');

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="csr_dashboard.css?v=<?php echo time(); ?>">

</head>
<body>

<header class="topbar">
  <div class="topbar-left">
    <button id="hamb" class="hamb">☰</button>
    <?php if($logoPath): ?>
      <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" class="logo">
    <?php endif; ?>
    <div class="title">CSR Dashboard — <strong><?= htmlspecialchars($csr_fullname) ?></strong></div>
  </div>

  <div class="topbar-tabs" id="top-tabs">
    <button class="tab pill active" data-tab="all">💬 All Clients</button>
    <button class="tab pill" data-tab="mine">👤 My Clients</button>
    <button class="tab pill" data-tab="rem">⏰ Reminders</button>
    <button class="tab pill" data-tab="survey" onclick="location.href='survey_responses.php'">📝 Survey Responses</button>
    <button class="tab pill" data-tab="profile" onclick="location.href='update_profile.php'">👤 Edit Profile</button>
  </div>

  <div class="topbar-right">
    <button id="themeToggle" class="icon">🌙</button>
  </div>
</header>

<div id="layout">
  <aside id="sidebar" class="sidebar">
    <div class="sidebar-inner">
      <div class="sidebar-controls">
        <button id="closeSidebar" onclick="toggleSidebar(false)">✖</button>
      </div>
      <div id="client-col" class="client-col">
        <!-- client list rendered by JS -->
        <div class="loading">Loading clients…</div>
      </div>
    </div>
  </aside>

  <main id="main">
    <section id="chat-col" class="chat-col">
      <div id="chat-head" class="chat-head">
        <div class="chat-title">
          <div id="chatAvatar" class="avatar">A</div>
          <div>
            <div id="chat-name">Select a client</div>
            <div id="chat-status" class="status">Offline</div>
          </div>
        </div>
        <div class="info-dot" title="Info">i</div>
      </div>

      <div id="messages" class="messages"></div>

      <div id="typingIndicator" class="typing">Typing…</div>

      <div id="composer" class="composer">
        <button id="emojiBtn" class="emoji">🙂</button>
        <input id="msg" type="text" placeholder="Type a reply…" autocomplete="off">
        <button id="sendBtn" class="send">Send</button>
      </div>
    </section>

    <aside id="reminders" class="reminders">
      <div class="rem-panel">
        <input id="rem-q" placeholder="Search reminders…" />
        <div id="rem-list"></div>
      </div>
    </aside>
  </main>
</div>

<script>
  const CSR_USER = <?= json_encode($csr_user) ?>;
  const CSR_FULLNAME = <?= json_encode($csr_fullname) ?>;
  const AJAX_URL = "csr_dashboard_ajax.php";
</script>
<script src="csr_dashboard.js?v=<?php echo time(); ?>"></script>

</body>
</html>
