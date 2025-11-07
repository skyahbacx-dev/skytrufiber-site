<?php
include '../db_connect.php';
$q=$conn->query("SELECT s.client_name,s.created_at,s.archived,a.name AS csr 
  FROM chat_sessions s 
  LEFT JOIN csr_accounts a ON a.id=s.assigned_csr_id 
  ORDER BY s.id DESC");
$out=[];
while($r=$q->fetch_assoc()) $out[]=$r;
echo json_encode($out);
?>
