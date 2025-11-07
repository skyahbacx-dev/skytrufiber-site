<?php
include '../db_connect.php';
$res = $conn->query("SELECT * FROM survey ORDER BY created_at DESC");
echo "<table><tr><th>ID</th><th>Technician</th><th>Client</th><th>Rating</th><th>Remarks</th><th>Date</th></tr>";
while ($r = $res->fetch_assoc()) {
  echo "<tr>
    <td>{$r['id']}</td>
    <td>".htmlspecialchars($r['tech_name'])."</td>
    <td>".htmlspecialchars($r['client_name'])."</td>
    <td>{$r['rating']}</td>
    <td>".htmlspecialchars($r['remarks'])."</td>
    <td>{$r['created_at']}</td>
  </tr>";
}
echo "</table>";
?>
