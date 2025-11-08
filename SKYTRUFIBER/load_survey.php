<?php
include '../db_connect.php';

try {
    $stmt = $conn->query("SELECT * FROM survey ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellspacing='0' cellpadding='6' style='border-collapse: collapse; width:100%;'>";
    echo "<tr style='background-color:#009900;color:white;'>
            <th>ID</th>
            <th>Technician</th>
            <th>Client</th>
            <th>Rating</th>
            <th>Remarks</th>
            <th>Date</th>
          </tr>";

    foreach ($rows as $r) {
        echo "<tr>
                <td>" . htmlspecialchars($r['id']) . "</td>
                <td>" . htmlspecialchars($r['tech_name']) . "</td>
                <td>" . htmlspecialchars($r['client_name']) . "</td>
                <td>" . htmlspecialchars($r['rating']) . "</td>
                <td>" . htmlspecialchars($r['remarks']) . "</td>
                <td>" . htmlspecialchars($r['created_at']) . "</td>
              </tr>";
    }

    echo "</table>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
