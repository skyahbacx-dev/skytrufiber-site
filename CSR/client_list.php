<?php
session_start();
include '../db_connect.php'; // This should define $conn as a PDO instance

$csr_user = $_SESSION['csr_user'] ?? '';
$tab = $_GET['tab'] ?? 'all';

if ($tab === 'mine') {
  $sql = "
    SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
    FROM clients c
    LEFT JOIN chat ch ON ch.client_id = c.id
    WHERE c.assigned_csr = :csr
    GROUP BY c.id, c.name, c.assigned_csr
    ORDER BY last_chat DESC NULLS LAST
  ";
  $stmt = $conn->prepare($sql);
  $stmt->execute([':csr' => $csr_user]);
} else {
  $sql = "
    SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
    FROM clients c
    LEFT JOIN chat ch ON ch.client_id = c.id
    GROUP BY c.id, c.name, c.assigned_csr
    ORDER BY last_chat DESC NULLS LAST
  ";
  $stmt = $conn->query($sql);
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $assigned = $row['assigned_csr'] ?: 'Unassigned';
  echo "<div class='client-item' data-id='" . htmlspecialchars($row['id']) . "' data-csr='" . htmlspecialchars($assigned, ENT_QUOTES) . "'>
          <strong>" . htmlspecialchars($row['name']) . "</strong><br>
          <small>Assigned: " . htmlspecialchars($assigned) . "</small>
        </div>";
}
?>
