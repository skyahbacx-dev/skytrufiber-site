<?php
session_start();
require "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;

$sql = "
    SELECT id, full_name, email, district, barangay, is_online, assigned_csr
    FROM users
    ORDER BY is_online DESC, full_name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => "success", "clients" => $clients]);

foreach ($rows as $row):
    $assigned = ($row["assigned_csr"] === $csr);
?>
<div class="client-item" data-id="<?= $row['id']; ?>">
    <img src="upload/default-avatar.png" class="client-avatar">
    <div class="client-info-box">
        <span class="client-name"><?= htmlspecialchars($row['full_name']); ?></span>
        <span class="client-email"><?= htmlspecialchars($row['email']); ?></span>
        <small><?= $row['district']; ?> - <?= $row['barangay']; ?></small>
    </div>
    <span class="status-dot <?= $row['is_online'] ? 'online' : 'offline' ?>"></span>
</div>
<?php endforeach; ?>
