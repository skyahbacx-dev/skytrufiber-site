<?php
include '../db_connect.php';
header('Content-Type: application/json');

$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$username  = trim($_GET['username'] ?? '');

/* ===========================================================
   1️⃣ LOAD CHAT BY CLIENT ID (CSR Dashboard)
   =========================================================== */
if ($client_id > 0) {
  $stmt = $conn->prepare("
    SELECT 
      ch.message, 
      ch.sender_type, 
      ch.created_at, 
      ch.assigned_csr,
      ch.csr_fullname,
      c.name AS client_name
    FROM chat ch
    JOIN clients c ON ch.client_id = c.id
    WHERE c.id = ?
    ORDER BY ch.created_at ASC
  ");
  $stmt->bind_param("i", $client_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $messages = [];
  while ($row = $result->fetch_assoc()) {
    $messages[] = [
      'message' => $row['message'],
      'sender_type' => $row['sender_type'],
      'time' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
      'client_name' => $row['client_name'],
      'assigned_csr' => $row['assigned_csr'],
      'csr_fullname' => $row['csr_fullname']
    ];
  }

  echo json_encode($messages);
  exit;
}

/* ===========================================================
   2️⃣ LOAD CHAT BY USERNAME (Client Chat)
   =========================================================== */
if ($username !== '') {
  $stmt = $conn->prepare("
    SELECT 
      ch.message, 
      ch.sender_type, 
      ch.created_at, 
      ch.assigned_csr,
      ch.csr_fullname,
      c.name AS client_name
    FROM chat ch
    JOIN clients c ON ch.client_id = c.id
    WHERE c.name = ?
    ORDER BY ch.created_at ASC
  ");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  $messages = [];
  while ($row = $result->fetch_assoc()) {
    $messages[] = [
      'message' => $row['message'],
      'sender_type' => $row['sender_type'],
      'time' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
      'client_name' => $row['client_name'],
      'assigned_csr' => $row['assigned_csr'],
      'csr_fullname' => $row['csr_fullname']
    ];
  }

  echo json_encode($messages);
  exit;
}

/* ===========================================================
   3️⃣ DEFAULT FALLBACK
   =========================================================== */
echo json_encode([]);
?>
