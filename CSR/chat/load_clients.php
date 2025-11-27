<?php
session_start();
require_once "../../db_connect.php";

try {
    // Fetch ALL clients from DB
    $query = $conn->prepare("SELECT id, full_name, account_number, district, barangay, is_online FROM users ORDER BY full_name ASC");
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
} catch (PDOException $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage());
}
