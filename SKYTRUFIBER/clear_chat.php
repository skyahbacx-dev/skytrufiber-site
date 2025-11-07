<?php
include '../db_connect.php';

// Get client name
$client_name = $_GET['username'] ?? '';
if (!$client_name) {
  echo json_encode(['status'=>'error','msg'=>'No username']);
  exit;
}

// Step 1: Delete previous chat messages for this client
$stmt = $conn->prepare("DELETE FROM chat_messages WHERE client_name = ?");
$stmt->bind_param("s", $client_name);
$stmt->execute();

// Step 2: Find an online CSR randomly
$csr = $conn->query("SELECT id, name FROM csr_accounts WHERE is_online = 1 ORDER BY RAND() LIMIT 1");
if ($csr && $csr->num_rows > 0) {
  $csrData = $csr->fetch_assoc();
  $csr_id = $csrData['id'];
  $csr_name = $csrData['name'];

  // Step 3: Create new chat session
  $insert = $conn->prepare("INSERT INTO chat_sessions (client_name, assigned_csr_id) VALUES (?, ?)");
  $insert->bind_param("si", $client_name, $csr_id);
  $insert->execute();

  // Step 4: Send CSR automated welcome message
  $welcome = "👋 Hi, this is CSR $csr_name. How can I assist you today?";
  $msg = $conn->prepare("INSERT INTO chat_messages (client_name, sender_type, assigned_csr_id, message) VALUES (?, 'csr', ?, ?)");
  $msg->bind_param("sis", $client_name, $csr_id, $welcome);
  $msg->execute();

  echo json_encode(['status'=>'success','csr'=>$csr_name]);
} else {
  echo json_encode(['status'=>'no_csr','msg'=>'No online CSR found']);
}
?>
