<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) { echo json_encode([]); exit; }

$stmt = $conn->prepare("
    SELECT 
        id,
        message,
        sender_type,
        media_path,
        media_type,
        csr_fullname,
        TO_CHAR(created_at, 'Mon DD, YYYY HH12:MI AM') AS created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY id ASC
");
$stmt->execute([":cid" => $client_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
