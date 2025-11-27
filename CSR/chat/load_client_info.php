<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

if (!isset($_POST["client_id"])) {
    echo "<p>No client selected.</p>";
    exit;
}

$clientID = $_POST["client_id"];

try {
    $stmt = $conn->prepare("
        SELECT full_name, email, district, barangay, is_online, assigned_csr, is_locked
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$clientID]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo "<p>Client not found.</p>";
        exit;
    }

    echo "
        <h3>" . htmlspecialchars($client['full_name'], ENT_QUOTES) . "</h3>
        <p><strong>Email:</strong> {$client['email']}</p>
        <p><strong>District:</strong> {$client['district']}</p>
        <p><strong>Barangay:</strong> {$client['barangay']}</p>
        <p><strong>Status:</strong> " . ($client['is_online'] ? "🟢 Online" : "🔴 Offline") . "</p>
        <p><strong>Assigned CSR:</strong> " . ($client['assigned_csr'] ?? "None") . "</p>
        <p><strong>Lock:</strong> " . ($client['is_locked'] ? "🔒 Locked" : "Unlocked") . "</p>
    ";

} catch (PDOException $e) {
    echo "<p>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>
