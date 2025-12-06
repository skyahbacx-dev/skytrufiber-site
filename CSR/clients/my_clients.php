<?php
include "../../db_connect.php";

$csrUser = $_SESSION["csr_user"];

// Search filters
$search = trim($_GET['search'] ?? '');

// Count clients assigned to CSR
$countStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM users 
    WHERE assigned_csr = :csr
      AND (full_name ILIKE :s OR account_number ILIKE :s OR email ILIKE :s)
");
$countStmt->execute([
    ":csr" => $csrUser,
    ":s"   => "%$search%"
]);
$totalClients = $countStmt->fetchColumn();

// Fetch list of clients assigned
$stmt = $conn->prepare("
    SELECT id, account_number, full_name, email, district, barangay, date_installed
    FROM users
    WHERE assigned_csr = :csr
      AND (full_name ILIKE :s OR account_number ILIKE :s OR email ILIKE :s)
    ORDER BY full_name ASC
");
$stmt->execute([
    ":csr" => $csrUser,
    ":s"   => "%$search%"
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<link rel="stylesheet" href="../clients/my_clients.css">

<h1>👥 My Clients</h1>

<div class="client-summary">
    <strong>Total Assigned Clients:</strong> <?= $totalClients ?>
</div>

<form method="GET" class="search-bar">
    <input type="hidden" name="tab" value="clients">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, account #, or email...">
    <button>Search</button>
</form>

<div class="table-wrapper">
    <table class="styled-table">
        <thead>
            <tr>
                <th>Account #</th>
                <th>Client Name</th>
                <th>Email</th>
                <th>District</th>
                <th>Barangay</th>
                <th>Date Installed</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['account_number']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['district']) ?></td>
                <td><?= htmlspecialchars($r['barangay']) ?></td>
                <td><?= htmlspecialchars($r['date_installed']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
