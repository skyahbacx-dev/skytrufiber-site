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
    $to = $_GET['to'] ?? '';

    $params = [':search' => $search];

    // --- Load from survey_responses ---
    $q1 = "
      SELECT id, client_name, account_name, location, feedback AS remarks, created_at, 'survey_responses' AS source
      FROM survey_responses
      WHERE (client_name ILIKE :search OR account_name ILIKE :search OR location ILIKE :search OR feedback ILIKE :search)
    ";

    // --- Load from survey ---
    $q2 = "
      SELECT id, client_name, tech_name AS account_name, NULL AS location, remarks, created_at, 'survey' AS source
      FROM survey
      WHERE (client_name ILIKE :search OR tech_name ILIKE :search OR remarks ILIKE :search)
    ";

    if ($from && $to) {
      $q1 .= " AND DATE(created_at) BETWEEN :from AND :to";
      $q2 .= " AND DATE(created_at) BETWEEN :from AND :to";
      $params[':from'] = $from;
      $params[':to'] = $to;
    }

    // Union both tables
    $query = "($q1) UNION ALL ($q2) ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
  }

  // ----- UPDATE (survey_responses only) -----
  if ($_GET['ajax'] === 'update' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $client = trim($_POST['client_name'] ?? '');
    $account = trim($_POST['account_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');

    $stmt = $conn->prepare("
      UPDATE survey_responses
      SET client_name = :client, account_name = :account, location = :location, feedback = :feedback
      WHERE id = :id
    ");
    $ok = $stmt->execute([
      ':client' => $client,
      ':account' => $account,
      ':location' => $location,
      ':feedback' => $feedback,
      ':id' => $id
    ]);
    echo $ok ? 'ok' : 'fail';
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Survey & Feedback — SkyTruFiber CSR Dashboard</title>
<style>
body {
  font-family:"Segoe UI",Arial,sans-serif;
  margin:0; height:100vh; display:flex;
  background:#f2fff2; overflow:hidden; position:relative;
}
body::before {
  content:""; position:absolute; inset:0;
  background:url('<?= $logoPath ?>') no-repeat center center;
  background-size:700px auto; opacity:0.05; z-index:0;
}
#sidebar {
  width:240px; background:#009900; color:#fff;
  position:fixed; left:0; top:0; bottom:0;
  transform:translateX(-100%); transition:.3s;
  display:flex; flex-direction:column; z-index:2;
}
#sidebar.active { transform:translateX(0); }
#sidebar h2 {
  margin:0; padding:15px; background:#007a00; text-align:center;
  display:flex; align-items:center; justify-content:center; gap:10px;
}
#sidebar h2 img { height:28px; }
#sidebar a { color:#fff; text-decoration:none; padding:15px 20px; display:block; font-weight:600; }
#sidebar a:hover { background:#00b300; }
#hamburger {
  background:#009900; color:#fff; padding:10px 14px; font-size:22px; cursor:pointer; border:none;
}
header {
  background:#00aa00; color:#fff; display:flex; justify-content:space-between;
  align-items:center; padding:10px 20px; font-weight:700; z-index:1;
}
header .title { display:flex; align-items:center; gap:15px; font-size:18px; }
header img { height:45px; }
header a { color:#fff; text-decoration:none; font-weight:bold; }
#tabs {
  display:flex; gap:8px; padding:8px 20px;
  background:rgba(230,255,230,0.95); border-bottom:1px solid #ccc;
}
.tab { padding:8px 14px; border-radius:8px; cursor:pointer; color:#007a00; font-weight:700; }
.tab.active { background:#009900; color:#fff; }
#main-content { flex:1; margin-left:0; transition:.3s; display:flex; flex-direction:column; z-index:1; }
#main-content.shifted { margin-left:240px; }
h1 { color:#006600; margin:20px; }
#filters {
  display:flex; flex-wrap:wrap; gap:12px; background:#eaffea;
  margin:0 20px; padding:10px 15px; border-radius:8px;
  align-items:center; justify-content:space-between;
}
#filters input {
  padding:6px 10px; border:1px solid #ccc; border-radius:6px; font-family:inherit;
}
#filters label { font-weight:600; color:#006600; }
table {
  border-collapse:collapse; width:95%; margin:20px auto;
  background:#fff; box-shadow:0 3px 10px rgba(0,0,0,.1);
  border-radius:10px; overflow:hidden;
}
th, td { padding:12px 15px; text-align:left; border-bottom:1px solid #eee; }
th { background:#009900; color:#fff; position:sticky; top:0; }
tr:hover { background:#e6ffe6; }
td.feedback { white-space:pre-wrap; word-break:break-word; color:#333; }
td.date { color:#666; font-size:13px; }
.edit-btn {
  background:#007a00; color:#fff; border:none; border-radius:6px; padding:6px 10px; cursor:pointer;
}
.edit-btn:hover { background:#00aa00; }
.no-data { text-align:center; padding:40px; color:#666; }
#editModal {
  position:fixed; top:0; left:0; right:0; bottom:0;
  background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:10;
}
#editModal .modal-content {
  background:#fff; padding:20px; border-radius:10px; width:90%; max-width:450px;
  box-shadow:0 2px 10px rgba(0,0,0,0.3);
}
#editModal input, #editModal textarea {
  width:100%; padding:8px; margin:6px 0; border:1px solid #ccc; border-radius:6px; font-family:inherit;
}
#editModal button {
  padding:8px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:bold;
}
#saveBtn { background:#00aa00; color:#fff; }
#saveBtn:hover { background:#007a00; }
#closeBtn { background:#ccc; }
@media (max-width:768px){
  #sidebar { width:220px; }
  table { width:98%; font-size:13px; }
  #filters { flex-direction:column; align-items:stretch; margin:10px; }
}
</style>
</head>
<body>

<div id="sidebar">
  <h2><img src="<?= $logoPath ?>" alt="Logo"> Menu</h2>
  <a href="csr_dashboard.php">💬 Chat Dashboard</a>
  <a href="csr_dashboard.php?tab=mine">👥 My Clients</a>
  <a href="survey_responses.php" style="background:#00b300;">📝 Survey & Feedback</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="main-content">
  <header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="title">
      <img src="<?= $logoPath ?>" alt="Logo">
      <span>Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
    <a href="csr_logout.php">Logout</a>
  </header>

  <div id="tabs">
    <div class="tab" onclick="goTo('csr_dashboard.php')">💬 All Clients</div>
    <div class="tab" onclick="goTo('csr_dashboard.php?tab=mine')">👥 My Clients</div>
    <div class="tab active">📝 Surveys & Feedback</div>
  </div>

  <h1>📝 Combined Customer Feedback</h1>

  <div id="filters">
    <div>
      <label>Search:</label>
      <input type="text" id="searchBox" placeholder="Client, Technician, Feedback, or Location...">
    </div>
    <div>
      <label>From:</label>
      <input type="date" id="fromDate">
      <label>To:</label>
      <input type="date" id="toDate">
      <button class="edit-btn" onclick="loadTable()">Filter</button>
    </div>
  </div>

  <div id="table-container"></div>
</div>

<!-- Edit Modal -->
<div id="editModal">
  <div class="modal-content">
    <h3>Edit Survey Response</h3>
    <input type="hidden" id="editId">
    <label>Client Name:</label>
    <input type="text" id="editClient">
    <label>Account/Technician Name:</label>
    <input type="text" id="editAccount">
    <label>Location (if applicable):</label>
    <input type="text" id="editLocation">
    <label>Feedback:</label>
    <textarea id="editFeedback" rows="5"></textarea>
    <div style="margin-top:10px;text-align:right;">
      <button id="closeBtn" onclick="closeModal()">Cancel</button>
      <button id="saveBtn" onclick="saveChanges()">Save</button>
    </div>
  </div>
</div>

<script>
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('active');
  document.getElementById('main-content').classList.toggle('shifted');
}
function goTo(url){window.location.href=url;}

function escapeHTML(str){
  if(!str) return '';
  return str.replace(/[&<>'"]/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[t]));
}

function loadTable(){
  const search=document.getElementById('searchBox').value.trim();
  const from=document.getElementById('fromDate').value;
  const to=document.getElementById('toDate').value;
  const params=new URLSearchParams({ajax:'load',search,from,to});

  fetch('survey_responses.php?'+params)
  .then(r=>r.json())
  .then(data=>{
    const cont=document.getElementById('table-container');
    if(!data.length){cont.innerHTML='<div class="no-data">No survey or feedback records found.</div>';return;}
    let html='<table><thead><tr><th>#</th><th>Client</th><th>Account/Technician</th><th>Location</th><th>Feedback</th><th>Source</th><th>Date</th><th>Action</th></tr></thead><tbody>';
    data.forEach((r,i)=>{
      html+=`<tr>
        <td>${i+1}</td>
        <td>${escapeHTML(r.client_name||'')}</td>
        <td>${escapeHTML(r.account_name||'')}</td>
        <td>${escapeHTML(r.location||'')}</td>
        <td class="feedback">${escapeHTML(r.remarks||'')}</td>
        <td>${r.source==='survey'?'🧾 Survey':'🗂️ Response'}</td>
        <td class="date">${new Date(r.created_at).toLocaleString()}</td>
        <td>${r.source==='survey_responses'
          ? `<button class="edit-btn" onclick="openModal(${r.id},'${escapeHTML(r.client_name)}','${escapeHTML(r.account_name)}','${escapeHTML(r.location)}','${escapeHTML(r.remarks)}')">Edit</button>`
          : `<span style='color:#777;'>Read-only</span>`}
        </td>
      </tr>`;
    });
    html+='</tbody></table>';
    cont.innerHTML=html;
  });
}

function openModal(id,client,account,location,feedback){
  document.getElementById('editId').value=id;
  document.getElementById('editClient').value=client;
  document.getElementById('editAccount').value=account;
  document.getElementById('editLocation').value=location;
  document.getElementById('editFeedback').value=feedback;
  document.getElementById('editModal').style.display='flex';
}
function closeModal(){document.getElementById('editModal').style.display='none';}

function saveChanges(){
  const id=document.getElementById('editId').value;
  const client=document.getElementById('editClient').value.trim();
  const account=document.getElementById('editAccount').value.trim();
  const location=document.getElementById('editLocation').value.trim();
  const feedback=document.getElementById('editFeedback').value.trim();

  fetch('survey_responses.php?ajax=update',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({id,client_name:client,account_name:account,location,feedback})
  }).then(r=>r.text()).then(resp=>{
    if(resp==='ok'){alert('✅ Survey updated successfully!');closeModal();loadTable();}
    else alert('❌ Failed to update.');
  });
}

document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
setInterval(loadTable,10000);
window.onload=loadTable;
document.getElementById('searchBox').addEventListener('keyup',()=>loadTable());
</script>
</body>
</html>
