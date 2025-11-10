<?php
session_start();
include '../db_connect.php';

// Ensure CSR is logged in
if (!isset($_SESSION['csr_user'])) {
  header("Location: csr_login.php");
  exit;
}
$csr_user = $_SESSION['csr_user'];

// Fetch CSR full name
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// ✅ Logo fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* === AJAX HANDLERS === */
if (isset($_GET['ajax'])) {

  // ----- LOAD BOTH TABLES -----
  if ($_GET['ajax'] === 'load') {
    $search = "%" . ($_GET['search'] ?? '') . "%";
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to']   ?? '';

    $params = [':search' => $search];

    // survey_responses (client feedback form)
    $q1 = "
      SELECT
        id,
        client_name,
        account_name,
        location,
        feedback AS remarks,
        created_at,
        'survey_responses' AS source
      FROM survey_responses
      WHERE (
        client_name ILIKE :search
        OR account_name ILIKE :search
        OR location ILIKE :search
        OR feedback ILIKE :search
      )
    ";

    // survey (technician visit feedback)
    $q2 = "
      SELECT
        id,
        client_name,
        tech_name AS account_name,
        NULL AS location,
        remarks,
        created_at,
        'survey' AS source
      FROM survey
      WHERE (
        client_name ILIKE :search
        OR tech_name ILIKE :search
        OR remarks ILIKE :search
      )
    ";

    if ($from && $to) {
      $q1 .= " AND DATE(created_at) BETWEEN :from AND :to";
      $q2 .= " AND DATE(created_at) BETWEEN :from AND :to";
      $params[':from'] = $from;
      $params[':to']   = $to;
    }

    $sql = "($q1) UNION ALL ($q2) ORDER BY created_at DESC";
    $st  = $conn->prepare($sql);
    $st->execute($params);

    echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
    exit;
  }

  // ----- UPDATE (survey_responses only) -----
  if ($_GET['ajax'] === 'update' && isset($_POST['id'])) {
    $id       = (int)$_POST['id'];
    $client   = trim($_POST['client_name']   ?? '');
    $account  = trim($_POST['account_name']  ?? '');
    $location = trim($_POST['location']      ?? '');
    $feedback = trim($_POST['feedback']      ?? '');

    $stmt = $conn->prepare("
      UPDATE survey_responses
      SET client_name = :client,
          account_name = :account,
          location = :location,
          feedback = :feedback
      WHERE id = :id
    ");
    $ok = $stmt->execute([
      ':client'   => $client,
      ':account'  => $account,
      ':location' => $location,
      ':feedback' => $feedback,
      ':id'       => $id
    ]);

    echo $ok ? 'ok' : 'fail';
    exit;
  }

  http_response_code(400);
  echo 'bad request';
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey & Feedback — SkyTruFiber CSR Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* ===== Base / Theme (matches CSR dashboard) ===== */
:root{
  --green:#009900;
  --green-dark:#006b00;
  --green-mid:#00aa00;
  --green-dim:#007a00;
  --bg:#f2fff2;
}
*{box-sizing:border-box}
body{
  font-family:"Segoe UI",Arial,sans-serif;
  margin:0;
  background:var(--bg);
  height:100vh;
  overflow:hidden;
  position:relative;
}
body::before{
  content:"";
  position:absolute; inset:0;
  background:url('<?= $logoPath ?>') no-repeat center center;
  background-size:700px auto;
  opacity:0.05; z-index:0; pointer-events:none;
}

/* ===== Overlay for sidebar ===== */
#overlay{
  position:fixed; inset:0;
  background:rgba(0,0,0,0.35);
  display:none; z-index:8;
}

/* ===== Sidebar ===== */
#sidebar{
  position:fixed; top:0; left:0;
  width:260px; height:100vh;
  background:var(--green-dark);
  color:#fff;
  transform:translateX(-100%);
  transition:transform .25s ease;
  z-index:9;
  box-shadow:5px 0 12px rgba(0,0,0,0.25);
}
#sidebar.active{ transform:translateX(0); }
#sidebar h2{
  margin:0; padding:18px;
  background:#005c00;
  text-align:center; font-size:18px;
  display:flex; align-items:center; justify-content:center; gap:10px;
}
#sidebar h2 img{ height:28px; }
#sidebar a{
  display:block; padding:14px 18px;
  text-decoration:none; color:#fff; font-weight:600;
}
#sidebar a:hover{ background:var(--green-mid); }

/* ===== Header ===== */
header{
  height:60px; background:var(--green);
  color:#fff; display:flex; align-items:center;
  justify-content:space-between;
  padding:0 16px;
  position:relative; z-index:1;
  font-weight:700;
}
#hamb{
  cursor:pointer; font-size:26px;
  background:none; border:none; color:#fff;
  transition:transform .2s;
}
#hamb.active{ transform:rotate(90deg); }
header .title{
  display:flex; align-items:center; gap:12px; font-size:18px;
}
header .title img{ height:36px; }

/* ===== Tabs ===== */
#tabs{
  display:flex; gap:8px; padding:8px 16px;
  background:rgba(230,255,230,0.95);
  border-bottom:1px solid #cde9cd;
  position:relative; z-index:1;
}
.tab{
  padding:8px 14px; border-radius:8px;
  cursor:pointer; color:#006b00; font-weight:700;
  user-select:none;
}
.tab.active{ background:var(--green-dark); color:#fff; }

/* ===== Main Content (full width) ===== */
#main{
  height:calc(100vh - 60px - 49px); /* header + tabs */
  overflow:auto; padding:18px; position:relative; z-index:1;
}

/* ===== Filters ===== */
#filters{
  display:flex; flex-wrap:wrap; gap:12px;
  background:#eaffea; border:1px solid #cfe7cf;
  padding:12px 14px; border-radius:10px;
  align-items:center; justify-content:space-between;
}
#filters .left, #filters .right{ display:flex; gap:10px; align-items:center; }
#filters label{ font-weight:700; color:#006600; }
#filters input{
  padding:8px 10px; border:1px solid #c9c9c9;
  border-radius:8px; font-family:inherit;
}

/* ===== Table ===== */
.card{
  margin-top:14px; background:#fff; border-radius:12px;
  box-shadow:0 4px 12px rgba(0,0,0,.10);
  overflow:hidden; border:1px solid #eef3ee;
}
table{
  width:100%; border-collapse:collapse;
}
th, td{ padding:12px 14px; text-align:left; border-bottom:1px solid #f0f5f0; }
th{
  background:var(--green-dark); color:#fff;
  position:sticky; top:0; z-index:2;
}
tr:hover{ background:#f4fff4; }
td.feedback{ white-space:pre-wrap; word-break:break-word; color:#333; }
td.date{ color:#666; font-size:13px; }

/* ===== Buttons ===== */
.btn{
  border:none; border-radius:8px; padding:8px 12px;
  font-weight:700; cursor:pointer;
}
.btn.primary{ background:var(--green); color:#fff; }
.btn.primary:hover{ background:var(--green-dim); }

/* ===== Modal ===== */
#editModal{
  position:fixed; inset:0;
  display:none; align-items:center; justify-content:center;
  background:rgba(0,0,0,.45); z-index:10;
}
.modal-content{
  width:92%; max-width:520px;
  background:#fff; border-radius:12px;
  padding:18px; box-shadow:0 10px 24px rgba(0,0,0,.25);
}
.modal-content h3{ margin:0 0 10px 0; color:#064; }
.modal-grid{ display:grid; grid-template-columns:1fr; gap:10px; }
.modal-grid label{ font-weight:700; color:#064; }
.modal-grid input, .modal-grid textarea{
  width:100%; padding:10px; border-radius:8px; border:1px solid #c9c9c9; font-family:inherit;
}
.modal-actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:12px; }
.btn.grey{ background:#ddd; color:#333; }
.btn.grey:hover{ background:#cfcfcf; }

/* ===== Responsive ===== */
@media (max-width:720px){
  #filters{ flex-direction:column; align-items:stretch; gap:8px; }
}
</style>
</head>
<body>

<!-- Overlay -->
<div id="overlay" onclick="toggleSidebar(false)"></div>

<!-- Sidebar -->
<div id="sidebar">
  <h2><img src="<?= $logoPath ?>" alt=""> Menu</h2>
  <a href="csr_dashboard.php?tab=all">💬 Chat Dashboard</a>
  <a href="csr_dashboard.php?tab=mine">👥 My Clients</a>
  <a href="csr_dashboard.php?tab=rem">⏰ Reminders</a>
  <a href="survey_responses.php" style="background:#00b300;">📝 Survey & Feedback</a>
  <a href="edit_profile.php">👤 Edit Profile</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- Header -->
<header>
  <button id="hamb" onclick="toggleSidebar()"><?php echo '☰'; ?></button>
  <div class="title">
    <img src="<?= $logoPath ?>" alt="Logo">
    <span>Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?></span>
  </div>
</header>

<!-- Tabs -->
<div id="tabs">
  <div class="tab" onclick="goTo('csr_dashboard.php?tab=all')">💬 All Clients</div>
  <div class="tab" onclick="goTo('csr_dashboard.php?tab=mine')">👥 My Clients</div>
  <div class="tab" onclick="goTo('csr_dashboard.php?tab=rem')">⏰ Reminders</div>
  <div class="tab active" onclick="goTo('survey_responses.php')">📝 Survey & Feedback</div>
</div>

<!-- Main (full width) -->
<div id="main">
  <!-- Filters -->
  <div id="filters">
    <div class="left">
      <label>Search</label>
      <input type="text" id="searchBox" placeholder="Client, Technician, Feedback, or Location…">
    </div>
    <div class="right">
      <label>From</label>
      <input type="date" id="fromDate">
      <label>To</label>
      <input type="date" id="toDate">
      <button class="btn primary" onclick="loadTable()">Filter</button>
    </div>
  </div>

  <!-- Table -->
  <div class="card" id="tableWrap">
    <div id="table-container" style="min-height:240px; padding:4px 0;"></div>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal">
  <div class="modal-content">
    <h3>Edit Survey Response</h3>
    <div class="modal-grid">
      <input type="hidden" id="editId">
      <div>
        <label>Client Name</label>
        <input type="text" id="editClient">
      </div>
      <div>
        <label>Account/Technician</label>
        <input type="text" id="editAccount">
      </div>
      <div>
        <label>Location (if applicable)</label>
        <input type="text" id="editLocation">
      </div>
      <div>
        <label>Feedback</label>
        <textarea id="editFeedback" rows="5"></textarea>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn grey" onclick="closeModal()">Cancel</button>
      <button class="btn primary" onclick="saveChanges()">Save</button>
    </div>
  </div>
</div>

<script>
/* ===== Sidebar toggle (no auto-open on tab click) ===== */
function toggleSidebar(force){
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('overlay');
  const hb = document.getElementById('hamb');
  const shouldOpen = (force === true) || (force === undefined && !sb.classList.contains('active'));

  if (shouldOpen){
    sb.classList.add('active'); ov.style.display='block'; hb.classList.add('active');
    hb.textContent = '✕';
  } else {
    sb.classList.remove('active'); ov.style.display='none'; hb.classList.remove('active');
    hb.textContent = '☰';
  }
}

function goTo(url){ window.location.href = url; }

/* ===== Utilities ===== */
function escapeHTML(str){
  if(!str) return '';
  return str.replace(/[&<>'"]/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[t]));
}

/* ===== Load table ===== */
function loadTable(){
  const search = document.getElementById('searchBox').value.trim();
  const from   = document.getElementById('fromDate').value;
  const to     = document.getElementById('toDate').value;
  const params = new URLSearchParams({ajax:'load',search,from,to});

  fetch('survey_responses.php?'+params.toString())
  .then(r=>r.json())
  .then(rows=>{
    const cont = document.getElementById('table-container');

    if(!rows || !rows.length){
      cont.innerHTML = `<div style="padding:18px; color:#666;">No survey or feedback records found.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th style="width:56px;">#</th>
            <th>Client</th>
            <th>Account / Technician</th>
            <th>Location</th>
            <th>Feedback</th>
            <th>Source</th>
            <th style="width:190px;">Date</th>
            <th style="width:120px;">Action</th>
          </tr>
        </thead>
        <tbody>
    `;

    rows.forEach((r, i) => {
      const canEdit = (r.source === 'survey_responses');
      html += `
        <tr>
          <td>${i+1}</td>
          <td>${escapeHTML(r.client_name||'')}</td>
          <td>${escapeHTML(r.account_name||'')}</td>
          <td>${escapeHTML(r.location||'')}</td>
          <td class="feedback">${escapeHTML(r.remarks||'')}</td>
          <td>${r.source==='survey' ? '🧾 Survey' : '🗂️ Response'}</td>
          <td class="date">${new Date(r.created_at).toLocaleString()}</td>
          <td>
            ${canEdit
              ? `<button class="btn primary" onclick="openModal(${r.id}, '${escapeHTML(r.client_name||'')}', '${escapeHTML(r.account_name||'')}', '${escapeHTML(r.location||'')}', '${escapeHTML((r.remarks||'').replace(/\\/g,'\\\\').replace(/\n/g,'\\n'))}')">Edit</button>`
              : `<span style="color:#777;">Read-only</span>`}
          </td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    cont.innerHTML = html;
  });
}

/* ===== Modal helpers ===== */
function openModal(id, client, account, location, feedback){
  document.getElementById('editId').value       = id;
  document.getElementById('editClient').value   = client;
  document.getElementById('editAccount').value  = account;
  document.getElementById('editLocation').value = location;
  document.getElementById('editFeedback').value = feedback.replace(/\\n/g, '\n');
  document.getElementById('editModal').style.display = 'flex';
}
function closeModal(){ document.getElementById('editModal').style.display = 'none'; }
document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeModal(); });

/* ===== Save changes (survey_responses only) ===== */
function saveChanges(){
  const id       = document.getElementById('editId').value;
  const client   = document.getElementById('editClient').value.trim();
  const account  = document.getElementById('editAccount').value.trim();
  const location = document.getElementById('editLocation').value.trim();
  const feedback = document.getElementById('editFeedback').value.trim();

  fetch('survey_responses.php?ajax=update',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({id, client_name:client, account_name:account, location, feedback})
  })
  .then(r=>r.text())
  .then(resp=>{
    if(resp==='ok'){
      alert('✅ Survey updated successfully!');
      closeModal();
      loadTable();
    } else {
      alert('❌ Failed to update.');
    }
  });
}

/* ===== Init ===== */
window.onload = ()=>{
  loadTable();
};
</script>
</body>
</html>
