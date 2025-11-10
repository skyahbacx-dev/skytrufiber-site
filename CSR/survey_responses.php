<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) { header("Location: csr_login.php"); exit; }
$csr_user = $_SESSION['csr_user'];

$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ---------- AJAX ---------- */
if (isset($_GET['ajax'])) {

  if ($_GET['ajax'] === 'load') {
    $search = "%".($_GET['search'] ?? '')."%";
    $from   = $_GET['from'] ?? '';
    $to     = $_GET['to']   ?? '';

    $sql = "
      SELECT id, client_name, account_name, district, location, feedback, created_at
      FROM survey_responses
      WHERE (client_name ILIKE :s OR account_name ILIKE :s OR location ILIKE :s OR feedback ILIKE :s OR district ILIKE :s)
    ";
    $params = [':s'=>$search];

    if ($from && $to) { $sql .= " AND DATE(created_at) BETWEEN :f AND :t"; $params[':f']=$from; $params[':t']=$to; }

    $sql .= " ORDER BY created_at DESC";
    $st = $conn->prepare($sql); $st->execute($params);
    echo json_encode($st->fetchAll(PDO::FETCH_ASSOC)); exit;
  }

  if ($_GET['ajax'] === 'update' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $client   = trim($_POST['client_name']  ?? '');
    $account  = trim($_POST['account_name'] ?? '');
    $district = trim($_POST['district']     ?? '');
    $location = trim($_POST['location']     ?? '');
    $feedback = trim($_POST['feedback']     ?? '');

    $ok = $conn->prepare("
      UPDATE survey_responses
      SET client_name=:c, account_name=:a, district=:d, location=:l, feedback=:f
      WHERE id=:id
    ")->execute([':c'=>$client,':a'=>$account,':d'=>$district,':l'=>$location,':f'=>$feedback,':id'=>$id]);

    echo $ok ? 'ok' : 'fail'; exit;
  }

  http_response_code(400); echo 'bad'; exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Surveys & Feedback — <?= htmlspecialchars($csr_fullname) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:#f6fff6;color:#1a1a1a}
header{background:#009900;color:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;font-weight:800}
header img{height:40px;margin-right:8px;vertical-align:middle}
.tools{display:flex;gap:10px;align-items:center;background:#eaffea;border-bottom:1px solid #cfe8cf;padding:10px 16px;flex-wrap:wrap}
.tools input, .tools button{padding:8px 10px;border:1px solid #c7c7c7;border-radius:8px}
.tools button{background:#009900;color:#fff;border:none;font-weight:700}
.wrap{padding:16px}
table{border-collapse:collapse;width:100%;background:#fff;border:1px solid #e6e6e6;border-radius:10px;overflow:hidden}
th,td{padding:12px 14px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
th{background:#009900;color:#fff}
tr:hover{background:#f7fff7}
button.edit{background:#007a00;color:#fff;border:none;border-radius:6px;padding:6px 10px;cursor:pointer}
.no{padding:30px;text-align:center;color:#666}
#modal{position:fixed;inset:0;background:rgba(0,0,0,.4);display:none;align-items:center;justify-content:center}
#card{background:#fff;padding:16px;border-radius:12px;width:95%;max-width:520px;box-shadow:0 8px 24px rgba(0,0,0,.2)}
#card h3{margin:0 0 10px}
#card input,#card textarea{width:100%;padding:9px;border:1px solid #c7c7c7;border-radius:8px;margin:6px 0}
#card .row{display:flex;gap:8px}
#card .row > *{flex:1}
#card footer{display:flex;justify-content:flex-end;gap:10px;margin-top:8px}
#save{background:#009900;color:#fff;border:none;border-radius:8px;padding:8px 12px;font-weight:800}
#close{background:#ddd;border:none;border-radius:8px;padding:8px 12px}
</style>
</head>
<body>

<header>
  <div><img src="<?= $logoPath ?>"> Surveys & Feedback — <?= htmlspecialchars($csr_fullname) ?></div>
  <nav>
    <a href="csr_dashboard.php" style="color:#fff;text-decoration:none;font-weight:700">Back to Dashboard</a>
  </nav>
</header>

<div class="tools">
  <input type="text" id="q" placeholder="Search client, account, district, location or feedback…" onkeyup="loadT()">
  <div>
    From <input type="date" id="f">
    To <input type="date" id="t">
  </div>
  <button onclick="loadT()">Filter</button>
</div>

<div class="wrap" id="wrap"></div>

<div id="modal">
  <div id="card">
    <h3>Edit Survey</h3>
    <input type="hidden" id="id">
    <div class="row">
      <input id="client" placeholder="Client name">
      <input id="acc" placeholder="Account / Technician">
    </div>
    <div class="row">
      <input id="dist" placeholder="District">
      <input id="loc" placeholder="Location / Barangay">
    </div>
    <textarea id="fb" rows="6" placeholder="Feedback"></textarea>
    <footer>
      <button id="close" onclick="closeM()">Cancel</button>
      <button id="save" onclick="save()">Save</button>
    </footer>
  </div>
</div>

<script>
function esc(s){return (s??'').replace(/[&<>"]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]))}
function loadT(){
  const p=new URLSearchParams({ajax:'load',search:document.getElementById('q').value.trim(),from:document.getElementById('f').value,to:document.getElementById('t').value});
  fetch('survey_responses.php?'+p).then(r=>r.json()).then(rows=>{
    const w=document.getElementById('wrap'); if(!rows.length){w.innerHTML='<div class="no">No survey responses found.</div>';return;}
    let h='<table><thead><tr><th>#</th><th>Client</th><th>Account / Tech</th><th>District</th><th>Location</th><th>Feedback</th><th>Date</th><th></th></tr></thead><tbody>';
    rows.forEach((r,i)=>{h+=`<tr>
      <td>${i+1}</td>
      <td>${esc(r.client_name)}</td>
      <td>${esc(r.account_name)}</td>
      <td>${esc(r.district||'')}</td>
      <td>${esc(r.location||'')}</td>
      <td>${esc(r.feedback)}</td>
      <td>${new Date(r.created_at).toLocaleString()}</td>
      <td><button class="edit" onclick="openM(${r.id},'${esc(r.client_name)}','${esc(r.account_name)}','${esc(r.district||'')}','${esc(r.location||'')}','${esc(r.feedback)}')">Edit</button></td>
    </tr>`});
    h+='</tbody></table>'; w.innerHTML=h;
  });
}
function openM(id,c,a,d,l,f){document.getElementById('id').value=id;document.getElementById('client').value=c;document.getElementById('acc').value=a;document.getElementById('dist').value=d;document.getElementById('loc').value=l;document.getElementById('fb').value=f;document.getElementById('modal').style.display='flex'}
function closeM(){document.getElementById('modal').style.display='none'}
function save(){
  const b=new URLSearchParams({id:document.getElementById('id').value,client_name:document.getElementById('client').value.trim(),account_name:document.getElementById('acc').value.trim(),district:document.getElementById('dist').value.trim(),location:document.getElementById('loc').value.trim(),feedback:document.getElementById('fb').value.trim()});
  fetch('survey_responses.php?ajax=update',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b}).then(r=>r.text()).then(t=>{if(t==='ok'){closeM();loadT();}else alert('Update failed');});
}
window.onload=loadT;
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeM()});
</script>
</body>
</html>
