<?php
include '../db_connect.php';

$username = $_GET['username'] ?? '';
if (!$username) {
  echo json_encode(['status'=>'error','msg'=>'No username']);
  exit;
}

// Find random online CSR
$csrQuery = $conn->query("SELECT id, name FROM csr_accounts WHERE is_online = 1 ORDER BY RAND() LIMIT 1");

if ($csrQuery && $csrQuery->num_rows > 0) {
  $csr = $csrQuery->fetch_assoc();
  $csr_id = $csr['id'];
  $csr_name = $csr['name'];
  $message = "👋 Hi $username! This is CSR $csr_name. How can I assist you today?";

  // Save greeting in chat_messages
  $stmt = $conn->prepare("INSERT INTO chat_messages (client_name, sender_type, assigned_csr_id, message)
                          VALUES (?, 'csr', ?, ?)");
  $stmt->bind_param("sis", $username, $csr_id, $message);
  $stmt->execute();

  echo json_encode(['status'=>'success','csr'=>$csr_name,'message'=>$message]);
} else {
  echo json_encode(['status'=>'no_csr','message'=>"👋 Hi $username! All CSRs are currently offline, but we’ll get back to you soon."]);
}
?>
