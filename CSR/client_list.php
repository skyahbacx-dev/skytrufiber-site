<?php
session_start();
include '../db_connect.php'; // Neon PDO connection

$csr_user = $_SESSION['csr_user'] ?? '';
if (!$csr_user) {
    die("Not logged in.");
}

// LOAD ALL CLIENTS, ORDERED BY MOST RECENT MESSAGE
$sql = "
    SELECT 
        c.id,
        c.name,
        c.assigned_csr,
        MAX(ch.created_at) AS last_chat,
        SUM(CASE 
                WHEN ch.sender_type = 'client' 
                     AND (ch.assigned_csr IS NULL OR ch.assigned_csr != :csr)
                THEN 1 ELSE 0 
            END) AS unread
    FROM clients c
    LEFT JOIN chat ch ON ch.client_id = c.id
    GROUP BY c.id, c.name, c.assigned_csr
    ORDER BY last_chat DESC NULLS LAST, c.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([':csr' => $csr_user]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $clientId = htmlspecialchars($row['id']);
    $clientName = htmlspecialchars($row['name']);
    $assigned = $row['assigned_csr'] ?: 'Unassigned';
    $assignedSafe = htmlspecialchars($assigned);

    // UNREAD BADGES
    $unread = intval($row['unread']);
    $badge = $unread > 0 
        ? "<span class='unread-badge' id='badge_$clientId'>$unread</span>" 
        : "";

    // ACTIVE CLASS FOR THIS CSR'S CLIENTS
    $isMine = ($assigned === $csr_user) ? "assigned-to-me" : "";

    echo "
    <div class='client-item $isMine' 
         onclick=\"openChat($clientId, '$clientName')\" 
         id='client_$clientId'>

        <div>
            <strong>$clientName</strong><br>
            <small>Assigned: $assignedSafe</small>
        </div>

        $badge
    </div>
    ";
}
?>
