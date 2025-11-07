<?php
include '../db_connect.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$client_name = $_POST['client_name'] ?? '';
$csr_user = $_POST['csr_user'] ?? '';

if ($action === 'typing_start') {
  $conn->query("UPDATE clients SET typing_csr = '$csr_user', typing_time = NOW() WHERE name = '$client_name'");
  echo json_encode(['status' => 'ok']);
  exit;
}

if ($action === 'typing_stop') {
  $conn->query("UPDATE clients SET typing_csr = NULL WHERE name = '$client_name'");
  echo json_encode(['status' => 'ok']);
  exit;
}

if ($action === 'check') {
  $res = $conn->query("SELECT typing_csr, typing_time FROM clients WHERE name = '$client_name'");
  $row = $res->fetch_assoc();
  if ($row && $row['typing_csr']) {
    echo json_encode(['typing' => true, 'csr' => $row['typing_csr']]);
  } else {
    echo json_encode(['typing' => false]);
  }
  exit;
}

echo json_encode(['status' => 'none']);
?>
