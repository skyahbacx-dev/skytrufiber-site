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
?>

<link rel="stylesheet" href="../reminders/reminders.css">

<h1>🔔 Billing Reminder Notifications</h1>

<form method="GET" class="reminder-search">
    <input type="text" name="account" placeholder="Search by account #" value="<?= htmlspecialchars($account) ?>">
    <input type="text" name="email" placeholder="Search by email" value="<?= htmlspecialchars($email) ?>">

    <label class="today-checkbox">
        <input type="checkbox" name="today" value="1" <?= $today ? "checked" : "" ?>>
        Today's reminders
    </label>

    <button type="submit">Filter</button>
</form>

<div class="reminder-container">
<?php if(empty($logs)): ?>
    <div class="no-reminders">No reminders found.</div>
<?php endif; ?>

<?php foreach($logs as $l): ?>
<div class="reminder-card">
    
    <div class="reminder-header">
        <span class="reminder-icon">🔔</span>
        <span class="reminder-title"><?= strtoupper(str_replace("_", " ", $l['reminder_type'])) ?> REMINDER</span>
    </div>

    <div class="reminder-body">
        <p><b>Account #:</b> <?= htmlspecialchars($l['account_number']) ?></p>
        <p><b>Email:</b> <?= htmlspecialchars($l['email']) ?></p>
        <p><b>Due Date:</b> <?= htmlspecialchars($l['due_date']) ?></p>
    </div>

    <div class="reminder-footer">
        <span class="status-badge <?= $l['status'] === 'sent' ? 'sent' : 'failed' ?>">
            <?= $l['status'] === 'sent' ? "✔ SENT" : "❌ FAILED" ?>
        </span>

        <span class="timestamp"><?= date("M d, Y h:i A", strtotime($l['sent_at'])) ?></span>
    </div>

</div>
<?php endforeach; ?>
</div>
