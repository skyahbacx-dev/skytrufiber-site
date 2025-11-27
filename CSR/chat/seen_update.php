<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;

if (!$client_id || !$csr) {
    exit("Missing data");
}

try {
    // Mark all client's unread messages as seen
    $updateSeen = $conn->prepare("
        UPDATE chat 
        SET seen = TRUE 
        WHERE client_id = ? 
        AND sender_type = 'client' 
        AND seen = FALSE
    ");
    $updateSeen->execute([$client_id]);

    // Update last read record for CSR
    $readCheck = $conn->prepare("
        SELECT id FROM chat_read WHERE client_id = ? AND csr = ?
    ");
    $readCheck->execute([$client_id, $csr]);
    $exists = $readCheck->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $updateRead = $conn->prepare("
            UPDATE chat_read SET last_read = NOW()
            WHERE id = ?
        ");
        $updateRead->execute([$exists["id"]]);
    } else {
        $insertRead = $conn->prepare("
            INSERT INTO chat_read (client_id, csr, last_read)
            VALUES (?, ?, NOW())
        ");
        $insertRead->execute([$client_id, $csr]);
    }

    echo "OK";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
