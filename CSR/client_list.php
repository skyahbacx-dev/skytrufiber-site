<?php
session_start();
include '../db_connect.php';

$csr_user = $_SESSION['csr_user'] ?? '';
$tab = $_GET['tab'] ?? 'all';

if ($tab === 'mine') {
  $sql = "SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
          FROM clients c
          LEFT JOIN chat ch ON ch.client_id = c.id
          WHERE c.assigned_csr = ?
          GROUP BY c.id, c.name, c.assigned_csr
          ORDER BY last_chat DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $csr_user);
  $stmt->execute();
  $clients = $stmt->get_result();
} else {
  $clients = $conn->query("
    SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
    FROM clients c
    LEFT JOIN chat ch ON ch.client_id = c.id
    GROUP BY c.id, c.name, c.assigned_csr
    ORDER BY last_chat DESC
  ");
}

while ($row = $clients->fetch_assoc()) {
  echo "<div class='client-item' data-id='{$row['id']}' data-csr='{$row['assigned_csr']}'>
          <strong>" . htmlspecialchars($row['name']) . "</strong><br>
          <small>Assigned: " . htmlspecialchars($row['assigned_csr'] ?? 'Unassigned') . "</small>
        </div>";
}
?>
