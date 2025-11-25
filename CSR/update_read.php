<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
$client_id = intval($_POST["client_id"] ?? 0);

if (!$csr || !$client_id) exit("ERR");

$conn->prepare("
    UPDATE chat SET seen = true 
    WHERE client_id = :cid AND sender_type = 'client' AND seen = false
")->execute([":cid" => $client_id]);

echo "ok";
exit;
