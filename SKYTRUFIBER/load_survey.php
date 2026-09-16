<?php
include '../db_connect.php';

try {
    // ✅ FIX — use the correct existing table
    $stmt = $conn->query("SELECT * FROM survey_responses ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "<p style='color:#777;'>No survey responses found.</p>";
        exit;
    }

    echo "<table border='1' cellspacing='0' cellpadding='6' style='border-collapse: collapse; width:100%;'>";
    echo "<tr style='background-color:#009900;color:white;'>
            <th>ID</th>
            <th>Client Name</th>
            <th>Account Number</th>
            <th>District</th>
            <th>Location</th>
            <th>Feedback</th>
            <th>Date</th>
            <th>Email</th>
          </tr>";

    foreach ($rows as $r) {
        echo "<tr>
                <td>" . htmlspecialchars($r['id']) . "</td>
                <td>" . htmlspecialchars($r['client_name']) . "</td>
                <td>" . htmlspecialchars($r['account_number']) . "</td>
                <td>" . htmlspecialchars($r['district']) . "</td>
                <td>" . htmlspecialchars($r['location']) . "</td>
                <td>" . htmlspecialchars($r['feedback']) . "</td>
                <td>" . htmlspecialchars($r['created_at']) . "</td>
                <td>" . htmlspecialchars($r['email']) . "</td>
              </tr>";
    }

    echo "</table>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
