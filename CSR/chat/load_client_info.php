<?php
require_once "../../db_connect.php";

$clientID = $_POST['id'];

$stmt = $conn->prepare("SELECT full_name, account_number, district, barangay, email, date_installed, is_online FROM users WHERE id = ?");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if ($client):
?>
    <p><strong>Name:</strong> <?= htmlspecialchars($client['full_name']) ?></p>
    <p><strong>Account #:</strong> <?= htmlspecialchars($client['account_number']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($client['email']) ?></p>
    <p><strong>District:</strong> <?= htmlspecialchars($client['district']) ?></p>
    <p><strong>Barangay:</strong> <?= htmlspecialchars($client['barangay']) ?></p>
    <p><strong>Status:</strong> <?= $client['is_online'] ? "🟢 Online" : "🔴 Offline" ?></p>
<?php else: ?>
    <p>No information found.</p>
<?php endif; ?>
