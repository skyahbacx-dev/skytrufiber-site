<?php
session_start();
require_once "../../db_connect.php";

try {
    $query = $conn->prepare("SELECT id, full_name, account_number, district, barangay, email, is_online FROM users ORDER BY full_name ASC");
    $query->execute();
    $clients = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!$clients) {
        echo "<p>No clients found.</p>";
        exit;
    }

    foreach ($clients as $client) {
        echo "<div class='client-item' onclick='selectClient(" . $client['id'] . ")'>
                <span>" . htmlspecialchars($client['full_name']) . "</span>
              </div>";
    }
} catch (Exception $e) {
    echo "Error loading clients.";
}
?>
