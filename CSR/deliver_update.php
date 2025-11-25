<?php
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = (int)($_POST["client_id"] ?? 0);

if (!$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

/* Mark CSR messages delivered to client */
$stmt = $conn->prepare("
    UPDATE chat
    SET delivered = true
    WHERE client_id = :cid
      AND delivered = false
");
$stmt->execute([":cid" => $client_id]);

echo json_encode(["status" => "ok"]);
exit;
?>
