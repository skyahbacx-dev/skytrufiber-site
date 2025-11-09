<?php
session_start();
include '../db_connect.php';

// Require login
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Get CSR name
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ================================
   ✅ AJAX ENDPOINTS
================================ */
if (isset($_GET['ajax'])) {

    /* ✅ Load all survey + legacy entries */
    if ($_GET['ajax'] === 'load') {

        $search = "%" . ($_GET['search'] ?? '') . "%";
        $from = $_GET['from'] ?? '';
        $to   = $_GET['to'] ?? '';

        $params = [":search" => $search];

        // Correct actual columns for survey_responses
        $q1 = "
            SELECT 
                id,
                client_name,
                account_name,
                district,
                location,
                feedback AS remarks,
                created_at,
                'survey_responses' AS source
            FROM survey_responses
            WHERE (
                client_name ILIKE :search OR
                account_name ILIKE :search OR
                district ILIKE :search OR
                location ILIKE :search OR
                feedback ILIKE :search
            )
        ";

        // Legacy "survey" table (different columns)
        $q2 = "
            SELECT
                id,
                client_name,
                tech_name AS account_name,
                NULL AS district,
                NULL AS location,
                remarks,
                created_at,
                'survey' AS source
            FROM survey
            WHERE (
                client_name ILIKE :search OR
                tech_name ILIKE :search OR
                remarks ILIKE :search
            )
        ";

        if ($from && $to) {
            $q1 .= " AND DATE(created_at) BETWEEN :from AND :to";
            $q2 .= " AND DATE(created_at) BETWEEN :from AND :to";
            $params[":from"] = $from;
            $params[":to"]   = $to;
        }

        $sql = "($q1) UNION ALL ($q2) ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* ✅ Update "survey_responses" only */
    if ($_GET['ajax'] === 'update' && isset($_POST['id'])) {

        $id        = (int) $_POST['id'];
        $client    = trim($_POST['client_name']);
        $account   = trim($_POST['account_name']);
        $district  = trim($_POST['district']);
        $location  = trim($_POST['location']);
        $feedback  = trim($_POST['feedback']);

        $stmt = $conn->prepare("
            UPDATE survey_responses
            SET client_name=:client,
                account_name=:account,
                district=:district,
                location=:location,
                feedback=:feedback
            WHERE id=:id
        ");

        $ok = $stmt->execute([
            ":client"   => $client,
            ":account"  => $account,
            ":district" => $district,
            ":location" => $location,
            ":feedback" => $feedback,
            ":id"       => $id
        ]);

        echo $ok ? "ok" : "fail";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Survey & Feedback — CSR Dashboard</title>

<style>
body {
  margin:0;
  font-family:"Segoe UI",sans-serif;
  background:#f6fff6;
  overflow:hidden;
}

#sidebar {
  position:fixed;
  top:0; left:0;
  width:260px;
  height:100vh;
  background:#006b00;
  color:white;
  transform:translateX(-100%);
  transition:0.3s;
  z-index:10;
}

#sidebar.active {
  transform:translateX(0);
}

#sidebar h2 {
  margin:0;
  padding:15px;
  background:#005c00;
  text-align:center;
}

#sidebar a {
  display:block;
  padding:15px 20px;
  text-decoration:none;
  color:white;
}
#sidebar a:hover {
  background:#009900;
}

header {
  height:60px;
  background:#009900;
  color:white;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:0 20px;
}

#hamburger {
  font-size:28px;
  background:none;
  border:none;
  color:white;
  cursor:pointer;
}

#tabs {
  background:#eaffea;
  padding:12px;
  display:flex;
  gap:10px;
  border-bottom:1px solid #c7e5c7;
}

.tab {
  padding:10px 18px;
  border-radius:6px;
  cursor:pointer;
  font-weight:600;
  color:#006b00;
}

.tab.active {
  background:#006b00;
  color:white;
}

#main-content {
  height:calc(100vh - 110px);
  overflow-y:auto;
  padding:20px;
}

table {
  width:100%;
  border-collapse:collapse;
  background:white;
  box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

th, td {
  padding:12px;
  border-bottom:1px solid #eee;
}

th {
  background:#006b00;
  color:white;
}

tr:hover {
  background:#e6ffe6;
}

.edit-btn {
  padding:6px 12px;
  background:#009900;
  color:white;
  border:none;
  border-radius:5px;
  cursor:pointer;
}

.edit-btn:hover {
  background:#007a00;
}

.no-data {
  padding:40px;
  text-align:center;
  color:#666;
}

#editModal {
  position:fixed;
  top:0; left:0;
  width:100%; height:100%;
  background:rgba(0,0,0,0.5);
  display:none;
  justify-content:center;
  align-items:center;
}

#editModal .modal-box {
  background:white;
  padding:20px;
  border-radius:10px;
  width:90%;
  max-width:450px;
}
</style>

</head>

<body>

<div id="sidebar">
  <h2>Menu</h2>
  <a href="csr_dashboard.php">💬 Chat Dashboard</a>
  <a href="csr_dashboard.php?tab=mine">👥 My Clients</a>
  <a href="csr_dashboard.php?tab=reminders">⏰ Reminders</a>
  <a style="background:#009900;">📝 Survey Responses</a>
  <a href="edit_profile.php">👤 Edit Profile</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
  <button id="hamburger" onclick="toggleSidebar()">☰</button>
  <span>Survey Responses — <?= htmlspecialchars($csr_fullname) ?></span>
</header>

<div id="tabs">
  <div class="tab" onclick="window.location='csr_dashboard.php'">💬 All Clients</div>
  <div class="tab" onclick="window.location='csr_dashboard.php?tab=mine'">👥 My Clients</div>
  <div class="tab active">📝 Survey Responses</div>
</div>

<div id="main-content">
  <h2>Customer Feedback Records</h2>

  <div style="margin-bottom:20px; display:flex; gap:10px;">
    <input id="searchBox" type="text" placeholder="Search client, account, district, feedback..." style="flex:1; padding:10px;">
    <input id="fromDate" type="date">
    <input id="toDate" type="date">
    <button onclick="loadTable()" class="edit-btn">Filter</button>
  </div>

  <div id="table-container"></div>
</div>

<!-- Modal -->
<div id="editModal">
  <div class="modal-box">
    <h3>Edit Survey Response</h3>

    <input type="hidden" id="editId">

    <label>Client Name:</label>
    <input type="text" id="editClient">

    <label>Account Name:</label>
    <input type="text" id="editAccount">

    <label>District:</label>
    <input type="text" id="editDistrict">

    <label>Location:</label>
    <input type="text" id="editLocation">

    <label>Feedback:</label>
    <textarea id="editFeedback" rows="5" style="width:100%;"></textarea>

    <div style="margin-top:10px; text-align:right;">
      <button onclick="closeModal()" style="padding:6px 12px;">Cancel</button>
      <button onclick="saveChanges()" class="edit-btn">Save</button>
    </div>
  </div>
</div>

<script>
/* Sidebar toggle */
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
}

/* Escape HTML */
function escapeHTML(str){
  return str?.replace(/[&<>\"']/g, t => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[t])) || '';
}

/* Load table */
function loadTable(){
  const search = document.getElementById('searchBox').value.trim();
  const from   = document.getElementById('fromDate').value;
  const to     = document.getElementById('toDate').value;

  fetch(`survey_responses.php?ajax=load&search=${search}&from=${from}&to=${to}`)
    .then(r=>r.json())
    .then(data=>{
      const cont=document.getElementById('table-container');

      if(!data.length){
        cont.innerHTML="<div class='no-data'>No feedback found.</div>";
        return;
      }

      let html="<table><tr><th>#</th><th>Client</th><th>Account</th><th>District</th><th>Location</th><th>Feedback</th><th>Source</th><th>Date</th><th>Action</th></tr>";

      data.forEach((r,i)=>{
        html+=`
          <tr>
            <td>${i+1}</td>
            <td>${escapeHTML(r.client_name)}</td>
            <td>${escapeHTML(r.account_name)}</td>
            <td>${escapeHTML(r.district)}</td>
            <td>${escapeHTML(r.location)}</td>
            <td>${escapeHTML(r.remarks)}</td>
            <td>${r.source === "survey" ? "Legacy" : "New"}</td>
            <td>${new Date(r.created_at).toLocaleString()}</td>
            <td>
              ${r.source==="survey_responses"
                ? `<button onclick="openModal(${r.id}, '${escapeHTML(r.client_name)}', '${escapeHTML(r.account_name)}', '${escapeHTML(r.district)}', '${escapeHTML(r.location)}', '${escapeHTML(r.remarks)}')" class='edit-btn'>Edit</button>`
                : "<span style='color:#888;'>Read-only</span>"
              }
            </td>
          </tr>
        `;
      });

      html+="</table>";
      cont.innerHTML=html;
    });
}

/* Open modal */
function openModal(id, client, account, district, location, feedback){
  document.getElementById("editId").value = id;
  document.getElementById("editClient").value = client;
  document.getElementById("editAccount").value = account;
  document.getElementById("editDistrict").value = district;
  document.getElementById("editLocation").value = location;
  document.getElementById("editFeedback").value = feedback;

  document.getElementById("editModal").style.display="flex";
}

/* Close modal */
function closeModal(){
  document.getElementById("editModal").style.display="none";
}

/* Save changes */
function saveChanges(){
  const id       = document.getElementById("editId").value;
  const client   = document.getElementById("editClient").value;
  const account  = document.getElementById("editAccount").value;
  const district = document.getElementById("editDistrict").value;
  const location = document.getElementById("editLocation").value;
  const feedback = document.getElementById("editFeedback").value;

  fetch("survey_responses.php?ajax=update", {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: new URLSearchParams({
          id, client_name:client, account_name:account,
          district, location, feedback
      })
  })
  .then(r=>r.text())
  .then(resp=>{
    if(resp==="ok"){
        alert("✅ Update successful!");
        closeModal();
        loadTable();
    } else {
        alert("❌ Update failed.");
    }
  });
}

/* Auto-load */
window.onload = loadTable;
document.getElementById("searchBox").addEventListener("keyup", ()=>loadTable());
</script>

</body>
</html>
