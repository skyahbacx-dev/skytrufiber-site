<?php
session_start();
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = trim($_SESSION['name'] ?? '');
$client_id = $_SESSION['client_id'] ?? 0;

/* ===========================================================
   1️⃣ DETERMINE CLIENT ID FROM SESSION OR USERNAME
   =========================================================== */
if ($client_id == 0 && $username !== '') {
    $stmt = $conn->prepare("SELECT id FROM clients WHERE name = :uname LIMIT 1");
    $stmt->execute([':uname' => $username]);
    $client_id = $stmt->fetchColumn();
    if ($client_id) $_SESSION['client_id'] = $client_id;
}

/* ===========================================================
   2️⃣ LOAD CHAT MESSAGES
   =========================================================== */
if ($client_id > 0) {
    $stmt = $conn->prepare("
        SELECT 
            ch.message, 
            ch.sender_type, 
            ch.created_at, 
            ch.assigned_csr,
            ch.csr_fullname,
            c.name AS client_name
        FROM chat ch
        JOIN clients c ON ch.client_id = c.id
        WHERE c.id = :cid
        ORDER BY ch.created_at ASC
    ");
    $stmt->execute([':cid' => $client_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $time = date('H:i', strtotime($row['created_at']));
        $senderType = $row['sender_type'];

        // Class + name logic
        if ($senderType === 'csr') {
            $class = 'csr';
            $senderName = htmlspecialchars($row['csr_fullname'] ?: 'CSR Agent');
        } elseif ($senderType === 'client') {
            $class = 'customer';
            $senderName = htmlspecialchars($row['client_name']);
        } else {
            $class = 'system';
            $senderName = 'System';
        }

        // Message bubble
        echo "
        <div class='message $class'>
          <div><strong>$senderName:</strong> " . htmlspecialchars($row['message']) . "</div>
          <div style='font-size:12px; color:#888;'>$time</div>
        </div>
        ";
    }
} else {
    echo "<div class='message system'>No chat messages found.</div>";
}
?>
