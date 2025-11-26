<?php
session_start();
require "../db_config.php";

$csr   = $_SESSION['csr_user'] ?? null;
$search = $_POST['search'] ?? '';

$sql = "
    SELECT id, full_name, email, district, barangay, assigned_csr, is_online
    FROM users
    WHERE full_name ILIKE :s OR email ILIKE :s
    ORDER BY is_online DESC, full_name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":s" => "%$search%"]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
