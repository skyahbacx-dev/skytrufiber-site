<?php
include __DIR__ . "/../../db_connect.php";


/* Search Filters */
$account = $_GET['account'] ?? '';
$email   = $_GET['email']   ?? '';
$today   = isset($_GET['today']) ? 1 : 0;

$sql = "SELECT * FROM reminder_logs WHERE 1=1";
$params = [];

if ($account !== '') {
    $sql .= " AND account_number ILIKE :acc";
    $params[':acc'] = "%$account%";
}

if ($email !== '') {
    $sql .= " AND email ILIKE :em";
    $params[':em'] = "%$email%";
}

if ($today) {
    $sql .= " AND sent_at::date = CURRENT_DATE";
}

$sql .= " ORDER BY sent_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sentCount = count(array_filter($logs, fn($l) => $l['status'] === 'sent'));
$failedCount = count($logs) - $sentCount;
?>

<link rel="stylesheet" href="/assets/css/skytru.css">

<div class="sky-page-header">
    <div>
        <h1>Reminders</h1>
        <p><?= count($logs) ?> reminders<?= $today ? " sent today" : "" ?> — <?= $sentCount ?> sent, <?= $failedCount ?> failed</p>
    </div>
</div>

<form method="GET" class="sky-card" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:20px;">
    <div class="sky-field" style="margin:0; flex:1; min-width:180px;">
        <label for="f-account">Account #</label>
        <input type="text" id="f-account" name="account" class="sky-input" placeholder="Search by account #" value="<?= htmlspecialchars($account) ?>">
    </div>
    <div class="sky-field" style="margin:0; flex:1; min-width:180px;">
        <label for="f-email">Email</label>
        <input type="text" id="f-email" name="email" class="sky-input" placeholder="Search by email" value="<?= htmlspecialchars($email) ?>">
    </div>
    <label style="display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--gray-700); padding-bottom:10px;">
        <input type="checkbox" name="today" value="1" <?= $today ? "checked" : "" ?>>
        Today only
    </label>
    <button type="submit" class="sky-btn sky-btn-primary">Filter</button>
</form>

<?php if (empty($logs)): ?>
    <div class="sky-card">
        <div class="sky-empty">
            <div class="sky-empty__icon">🔔</div>
            No reminders found.
        </div>
    </div>
<?php else: ?>
    <div class="sky-card-grid">
        <?php foreach ($logs as $l): ?>
            <div class="sky-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                    <span style="font-weight:700; font-size:13px;">🔔 <?= htmlspecialchars(strtoupper(str_replace("_", " ", $l['reminder_type']))) ?></span>
                    <span class="sky-badge <?= $l['status'] === 'sent' ? 'sky-badge-success' : 'sky-badge-danger' ?>">
                        <?= $l['status'] === 'sent' ? "✔ Sent" : "✖ Failed" ?>
                    </span>
                </div>
                <p style="margin:4px 0; font-size:13px;"><strong>Account:</strong> <?= htmlspecialchars($l['account_number']) ?></p>
                <p style="margin:4px 0; font-size:13px;"><strong>Email:</strong> <?= htmlspecialchars($l['email']) ?></p>
                <p style="margin:4px 0; font-size:13px;"><strong>Due:</strong> <?= htmlspecialchars($l['due_date']) ?></p>
                <p style="margin:10px 0 0; font-size:12px; color:var(--gray-500);"><?= date("M j, Y g:i A", strtotime($l['sent_at'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>