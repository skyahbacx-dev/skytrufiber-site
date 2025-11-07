<?php
include '../db_connect.php';
header('Content-Type: application/json');

$sender_type  = $_POST['sender_type'] ?? 'client';
$message      = trim($_POST['message'] ?? '');
$username     = trim($_POST['username'] ?? '');
$client_id    = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$csr_user     = $_POST['csr_user'] ?? '';
$csr_fullname = $_POST['csr_fullname'] ?? '';

if ($message === '') {
  echo json_encode(['status' => 'error', 'msg' => 'Empty message']);
  exit;
}

/* ===========================================================
   1️⃣ CLIENT MESSAGE HANDLER
   =========================================================== */
if ($sender_type === 'client') {
  // Find or create client
  $stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = ? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $res = $stmt->get_result();

  $assigned_csr = '';
  $assigned_csr_full = '';

  if ($res->num_rows === 0) {
    // Pick a random online CSR
    $csrQ = $conn->query("SELECT username, full_name FROM csr_users WHERE status='active' AND is_online=1 ORDER BY RAND() LIMIT 1");

    if ($csrQ && $csrQ->num_rows > 0) {
      $csr = $csrQ->fetch_assoc();
      $assigned_csr = $csr['username'];
      $assigned_csr_full = $csr['full_name'];
    } else {
      $assigned_csr = 'Unassigned';
      $assigned_csr_full = '';
    }

    // Create new client
    $ins = $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at) VALUES (?, ?, NOW())");
    $ins->bind_param("ss", $username, $assigned_csr);
    $ins->execute();
    $client_id = $ins->insert_id;
  } else {
    $row = $res->fetch_assoc();
    $client_id = $row['id'];
    $assigned_csr = $row['assigned_csr'];

    // Get full name of assigned CSR
    $csrNameQ = $conn->prepare("SELECT full_name FROM csr_users WHERE username = ? LIMIT 1");
    $csrNameQ->bind_param("s", $assigned_csr);
    $csrNameQ->execute();
    $csrFullRes = $csrNameQ->get_result();
    $assigned_csr_full = ($csrFullRes && $csrFullRes->num_rows > 0)
      ? $csrFullRes->fetch_assoc()['full_name']
      : $assigned_csr;
  }

  // Save client message
  $stmt2 = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, created_at) VALUES (?, 'client', ?, NOW())");
  $stmt2->bind_param("is", $client_id, $message);
  $stmt2->execute();

  // Check if it’s the first chat, send automated greeting
  $existing = $conn->prepare("SELECT COUNT(*) AS total FROM chat WHERE client_id = ?");
  $existing->bind_param("i", $client_id);
  $existing->execute();
  $count = $existing->get_result()->fetch_assoc()['total'];

  if ($count <= 2 && $assigned_csr !== 'Unassigned') {
    $greeting = "👋 Hi $username! This is $assigned_csr_full from SkyTruFiber. Thank you for reaching out. How can I assist you today?";
    $stmt3 = $conn->prepare("
      INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
      VALUES (?, 'csr', ?, ?, ?, NOW())
    ");
    $stmt3->bind_param("isss", $client_id, $greeting, $assigned_csr, $assigned_csr_full);
    $stmt3->execute();
  }

  echo json_encode(['status' => 'ok', 'client_id' => $client_id]);
  exit;
}

/* ===========================================================
   2️⃣ CSR MESSAGE HANDLER
   =========================================================== */
if ($sender_type === 'csr' && $client_id > 0) {
  $stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
    VALUES (?, 'csr', ?, ?, ?, NOW())
  ");
  $stmt->bind_param("isss", $client_id, $message, $csr_user, $csr_fullname);
  $stmt->execute();

  echo json_encode(['status' => 'ok']);
  exit;
}

/* ===========================================================
   3️⃣ INVALID REQUEST
   =========================================================== */
echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
?>
