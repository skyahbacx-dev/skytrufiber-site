<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;
$message  = trim($_POST["message"] ?? "");

if (!$csrUser || !$clientID || !$message) {
    echo "Invalid";
    exit;
}

try {
    // check lock state first
    $check = $conn->prepare("SELECT is_locked FROM users WHERE id = ?");
    $check->execute([$clientID]);
    $locked = $check->fetch(PDO::FETCH_ASSOC)["is_locked"];

    if ($locked) {
        echo "LOCKED";  // handled in chat.js
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (:cid, 'csr', :msg, 0, 0, NOW())
    ");
    $stmt->execute([
        ":cid" => $clientID,
        ":msg" => $message
    ]);

    echo "OK";

} catch (PDOException $e) {
    echo "ERR: ".$e->getMessage();
}
?>
