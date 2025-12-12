<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    die("Unauthorized");
}

require __DIR__ . "/../../db_connect.php";

$csrUser = $_SESSION["csr_user"];
$today = date("Y-m-d");


// -----------------------------------------
// FETCH TODAY'S TICKET STATISTICS
// -----------------------------------------
$stats = $conn->prepare("
    SELECT 
        COUNT(*) FILTER (WHERE status = 'unresolved') AS unresolved,
        COUNT(*) FILTER (WHERE status = 'pending') AS pending,
        COUNT(*) FILTER (WHERE status = 'resolved') AS resolved,
        COUNT(*) AS total
    FROM tickets
    WHERE assigned_csr = :csr
      AND DATE(updated_at) = :today
");
$stats->execute([":csr" => $csrUser, ":today" => $today]);
$counts = $stats->fetch(PDO::FETCH_ASSOC);


// -----------------------------------------
// FETCH TODAY'S TICKETS
// -----------------------------------------
$stmt = $conn->prepare("
    SELECT 
        t.id,
        t.status,
        t.created_at,
        t.updated_at,
        u.full_name AS client_name,
        u.account_number,
        u.district,
        u.barangay
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.assigned_csr = :csr
      AND DATE(t.updated_at) = :today
    ORDER BY t.updated_at DESC
");
$stmt->execute([
    ":csr" => $csrUser,
    ":today" => $today
]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
<title>Daily Ticket Summary</title>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 30px;
    background: #fff;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.header img {
    width: 120px;
    margin-bottom: 10px;
}

h2 {
    margin: 5px 0;
}

.summary-cards {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.card {
    padding: 15px 20px;
    border-radius: 8px;
    background: #f2f2f2;
    font-size: 18px;
    flex: 1;
    text-align: center;
}

.table-wrapper {
    margin-top: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th {
    background: #007bff;
    color: white;
    padding: 8px;
    font-size: 14px;
}

td {
    padding: 8px;
    border: 1px solid #ccc;
    font-size: 13px;
}

.status-resolved {
    color: green;
    font-weight: bold;
}
.status-pending {
    color: orange;
    font-weight: bold;
}
.status-unresolved {
    color: red;
    font-weight: bold;
}

@media print {
    .no-print {
        display: none;
    }
}
</style>

</head>
<body>

<div class="header">
    <img src="/AHBA_LOGO.png" alt="AHBA Logo">
    <h2>Daily Ticket Summary</h2>
    <p>CSR: <strong><?= htmlspecialchars($csrUser) ?></strong></p>
    <p>Date: <strong><?= $today ?></strong></p>
</div>

<div class="summary-cards">
    <div class="card">📌 Total Tickets: <strong><?= $counts['total'] ?></strong></div>
    <div class="card">🟥 Unresolved: <strong><?= $counts['unresolved'] ?></strong></div>
    <div class="card">🟧 Pending: <strong><?= $counts['pending'] ?></strong></div>
    <div class="card">🟩 Resolved: <strong><?= $counts['resolved'] ?></strong></div>
</div>

<hr>

<h3>Today's Ticket Details</h3>

<?php if (!$tickets): ?>
    <p>No tickets updated today.</p>
<?php else: ?>

<div class="table-wrapper">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Client</th>
            <th>District</th>
            <th>Status</th>
            <th>Date Created</th>
            <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($tickets as $t): ?>
        <tr>
            <td><?= $t["id"] ?></td>
            <td><?= htmlspecialchars($t["client_name"]) ?></td>
            <td><?= htmlspecialchars($t["district"]) ?></td>
            <td class="status-<?= strtolower($t["status"]) ?>">
                <?= strtoupper($t["status"]) ?>
            </td>
            <td><?= substr($t["created_at"], 0, 16) ?></td>
            <td><?= substr($t["updated_at"], 0, 16) ?></td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
</div>

<?php endif; ?>

<br><br>

<button class="no-print" onclick="window.print()" 
style="padding:10px 20px;font-size:16px;background:#007bff;color:white;border:none;border-radius:5px;">
    🖨 Print / Save as PDF
</button>

</body>
</html>
