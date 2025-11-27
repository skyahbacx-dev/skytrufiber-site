<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    echo "0";
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT typing 
        FROM typing_status 
        WHERE client_id = ?
        LIMIT 1
    ");
    $stmt->execute([$client_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo ($data && $data["typing"] == 1) ? "1" : "0";

} catch (Exception $e) {
    echo "0";
}
