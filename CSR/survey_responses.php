<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) { header("Location: csr_login.php"); exit; }
$csr_user = $_SESSION['csr_user'];

$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username=:u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$csr_fullname = $stmt->fetchColumn() ?: $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

if (isset($_GET['ajax'])) {
  if ($_GET['ajax']==='load') {
    $search = "%".($_GET['search']??'')."%";
    $from   = $_GET['from'] ?? '';
    $to     = $_GET['to']   ?? '';

    $sql = "
      SELECT id, client_name, account_name, email, district, location, feedback, created_at
      FROM survey_responses
      WHERE (client_name ILIKE :s OR account_name ILIKE :s OR email ILIKE :s OR district ILIKE :s OR location ILIKE :s OR feedback ILIKE :s)
    ";
    $params = [':s'=>$search];

    if ($from && $to) {
      $sql .= " AND DATE(created_at) BETWEEN :f AND :t";
      $params[':f']=$from; $params[':t']=$to;
    }
    $sql.=" ORDER BY created_at DESC";
    $st=$conn->prepare($sql); $st->execute($params);
    echo json_encode($st->fetchAll(PDO::FETCH_ASSOC)); exit;
  }

  if ($_GET['ajax']==='update' && isset($_POST['id'])) {
    $st=$conn->prepare("
      UPDATE survey_responses
      SET client_name=:c, account_name=:a, email=:e, district=:d, location=:l, feedback=:f
      WHERE id=:id
    ");
    $ok=$st->execute([
      ':c'=>trim($_POST['client_name']??''),
      ':a'=>trim($_POST['account_name']??''),
      ':e'=>trim($_POST['email']??''),
      ':d'=>trim($_POST['district']??''),
      ':l'=>trim($_POST['location']??''),
      ':f'=>trim($_POST['feedback']??''),
      ':id'=>(int)$_POST['id']
    ]);
    echo $ok?'ok':'fail'; exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey Responses — SkyTruFiber</title>
<style>
body{font-family:"Segoe UI",Arial,sans-serif;margin:0;height:100vh;display:flex;background:#f2fff2;overflow:hidden;position:relative}
body::before{content:"";position:absolute;inset:0;background:url('<?= $logoPath ?>') no-repeat center;background-size:700px auto;opacity:0.05;z-index:0}
#sidebar{width:240px;background:#009900;color:#fff;position:fixed;left:0;top:0;bottom:0;transform:translateX(-100%);transition:.3s;display:flex;flex-direction:column;z-index:2}
#sidebar.active{transform:translateX(0)}
#sidebar h2{margin:0;padding:15px;background:#007a00;text-align:center;display:flex;align-items:center;justify-content:center;gap:10px}
#sidebar h2 img{height:28px}
#sidebar a{color:#fff;text-decoration:none;padding:15px 20px;display:block;font-weight:600}
#sidebar a:hover{background:#00b300}
#hamburger{background:#009900;color:#fff;padding:10px 14px;font-size:22px;cursor:pointer;border:none}
header{background:#00aa00;color:#fff;display:flex;justify-content:space-between;align-items:center;padding:10px 20px;font-weight:700;z-index:1}
header .title{display:flex;align-items:center;gap:15px;font-size:18px}
header img{height:45px}
#tabs{display:flex;gap:8px;padding:8px 20px;background:rgba(230,255,230,0.95);border-bottom:1px solid #ccc}
.tab{padding:8px 14px;border-radius:8px;cursor:pointer;color:#007a00;font-weight:700}
.tab.active{background:#009900;color:#fff}
#main{flex:1;margin-left:0;transition:.3s;display:flex;flex-direction:column;z-index:1}
#main.shifted{margin-left:240px}
h1{color:#006600;margin:20px}
#filters{display:flex;flex-wrap:wrap;gap:12px;background:#eaffea;margin:0 20px;padding:10px 15px;border-radius:8px;align-items:center;justify-content:space-between}
#filters input{padding:6px 10px;border:1px solid #ccc;border-radius:6px}
table{border-collapse:collapse;width:95%;margin:20px auto;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-radius:10px;overflow:hidden}
th,td{padding:12px 15px;text-align:left;border-bottom:1px solid #eee}
th{background:#009900;color:#fff;position:sticky;top:0}
tr:hover{background:#e6ffe6}
td.feedback{white-space:pre-wrap;word-break:break-word;color:#333}
td.date{color:#666;font-size:13px}
.edit-btn{background:#007a00;color:#fff;border:none;border-radius:6px;padding:6px 10px;cursor:pointer}
.edit-btn:hover{background:#00aa00}
.no-data{text-align:center;padding:40px;color:#666}
#editModal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:10}
#editModal .modal-content{background:#fff;padding:20px;border-radius:10px;width:90%;max-width:520px;box-shadow:0 2px 10px rgba(0,0,0,.3)}
#editModal input,#editModal textarea{width:100%;padding:8px;margin:6px 0;border:1px solid #ccc;border-radius:6px}
#editModal button{padding:8px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:bold}
#saveBtn{background:#00aa00;color:#fff}
#saveBtn:hover{background:#007a00}
#closeBtn{background:#ccc}
</style>
</head>
<body>
<div id="sidebar">
  <h2><img src="<?= $logoPath ?>" alt=""> Menu</h2>
  <a href="csr_dashboard.php">💬 Chat Dashboard</a>
  <a href="csr_dashboard.php?tab=mine">👥 My Clients</a>
  <a href="survey_responses.php" style="background:#00b300;">📝 Survey Responses</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="main">
  <header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="title">
      <img src="<?= $logoPath ?>" alt="">
      <span>Survey Responses — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
    <a href="csr_logout.php" style="color:#fff;text-decoration:none">Logout</a>
  </header>

  <div id="tabs">
    <div class="tab" onclick="goTo('csr_dashboard.php')">💬 All Clients</div>
    <div class="tab" onclick="goTo('csr_dashboard.php?tab=mine')">👤 My Clients</div>
    <div class="tab active">📝 Survey Responses</div>
  </div>

  <h1>📝 Customer Feedback (Unified)</h1>

  <div id="filters">
    <div>
      <label>Search:</label>
      <input type="text" id="searchBox" placeholder="Client / Account / Email / District / Barangay / Feedback...">
    </div>
    <div>
      <label>From:</label><input type="date" id="fromDate">
      <label>To:</label><input type="date" id="toDate">
      <button class="edit-btn" onclick="loadTable()">Filter</button>
    </div>
  </div>

  <div id="table-container"></div>
</div>

<!-- Edit Modal -->
<div id="editModal">
  <div class="modal-content">
    <h3>Edit Feedback</h3>
    <input type="hidden" id="editId">
    <label>Client Name:</label><input type="text" id="editClient">
    <label>Account Number:</label><input type="text" id="editAccount">
    <label>Email:</label><input type="email" id="editEmail">
    <label>District:</label><input type="text" id="editDistrict">
    <label>Barangay:</label><input type="text" id="editLocation">
    <label>Feedback:</label><textarea id="editFeedback" rows="5"></textarea>
    <div style="margin-top:10px;text-align:right">
      <button id="closeBtn" onclick="closeModal()">Cancel</button>
      <button id="saveBtn" onclick="saveChanges()">Save</button>
    </div>
  </div>
</div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('active');document.getElementById('main').classList.toggle('shifted');}
function goTo(u){window.location.href=u;}
function esc(s){return (s||'').replace(/[&<>'"]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}

function loadTable(){
  const params=new URLSearchParams({ajax:'load',search:document.getElementById('searchBox').value.trim(),from:document.getElementById('fromDate').value,to:document.getElementById('toDate').value});
  fetch('survey_responses.php?'+params).then(r=>r.json()).then(rows=>{
    const c=document.getElementById('table-container');
    if(!rows.length){c.innerHTML='<div class="no-data">No feedback found.</div>';return;}
    let html='<table><thead><tr><th>#</th><th>Client</th><th>Account #</th><th>Email</th><th>District</th><th>Barangay</th><th>Feedback</th><th>Date</th><th>Action</th></tr></thead><tbody>';
    rows.forEach((r,i)=>{
      html+=`<tr>
      <td>${i+1}</td>
      <td>${esc(r.client_name)}</td>
      <td>${esc(r.account_name)}</td>
      <td>${esc(r.email||'')}</td>
      <td>${esc(r.district||'')}</td>
      <td>${esc(r.location||'')}</td>
      <td class="feedback">${esc(r.feedback||'')}</td>
      <td class="date">${new Date(r.created_at).toLocaleString()}</td>
      <td><button class="edit-btn" onclick="openModal(${r.id},'${esc(r.client_name)}','${esc(r.account_name)}','${esc(r.email||'')}','${esc(r.district||'')}','${esc(r.location||'')}','${esc(r.feedback||'')}')">Edit</button></td>
      </tr>`;
    });
    html+='</tbody></table>'; c.innerHTML=html;
  });
}
function openModal(id,c,a,e,d,l,f){document.getElementById('editId').value=id;document.getElementById('editClient').value=c;document.getElementById('editAccount').value=a;document.getElementById('editEmail').value=e;document.getElementById('editDistrict').value=d;document.getElementById('editLocation').value=l;document.getElementById('editFeedback').value=f;document.getElementById('editModal').style.display='flex';}
function closeModal(){document.getElementById('editModal').style.display='none';}
function saveChanges(){
  const body=new URLSearchParams({
    id:document.getElementById('editId').value,
    client_name:document.getElementById('editClient').value.trim(),
    account_name:document.getElementById('editAccount').value.trim(),
    email:document.getElementById('editEmail').value.trim(),
    district:document.getElementById('editDistrict').value.trim(),
    location:document.getElementById('editLocation').value.trim(),
    feedback:document.getElementById('editFeedback').value.trim()
  });
  fetch('survey_responses.php?ajax=update',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(r=>r.text()).then(t=>{ if(t==='ok'){alert('✅ Saved.');closeModal();loadTable();} else alert('❌ Failed to save.');});
}
setInterval(loadTable,10000);
window.onload=loadTable;
document.getElementById('searchBox').addEventListener('keyup',loadTable);
</script>
</body>
</html>
