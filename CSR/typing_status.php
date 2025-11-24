<?php
include "../db_connect.php";

$clientId = $_GET['client_id'] ?? null;
if (!$clientId) exit("stop");

$stmt = $conn->prepare("
    SELECT is_typing, updated_at
    FROM typing_status
    WHERE client_id = :client_id
    ORDER BY updated_at DESC
    LIMIT 1
");
$stmt->execute([":client_id" => $clientId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) exit("stop");

// auto-stop typing after 2 seconds without update
$lastUpdate = strtotime($row["updated_at"]);
if (time() - $lastUpdate > 2) {
    exit("stop");
}

echo $row["is_typing"] ? "typing" : "stop";
