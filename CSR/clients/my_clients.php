<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$csrUser = $_SESSION["csr_user"];
$search = trim($_GET["search"] ?? "");
?>

<link rel="stylesheet" href="my_clients.css">

<h1>👥 My Clients</h1>

<!-- SEARCH BAR -->
<form method="GET" class="search-bar">
    <input type="hidden" name="tab" value="clients">
    <input type="text" name="search" placeholder="Search clients..."
           value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
</form>

<?php
/* ============================================================
   FETCH ASSIGNED CLIENTS
============================================================ */
$query = "
    SELECT 
        id, 
        account_number, 
        full_name, 
        email, 
        district, 
        barangay, 
        date_installed
    FROM users
    WHERE assigned_csr = :csr
";

$params = [":csr" => $csrUser];

if ($search !== "") {
    $query .= " 
        AND (
            full_name ILIKE :s OR 
            account_number ILIKE :s OR 
            email ILIKE :s
        )
    ";
    $params[":s"] = "%$search%";
}

$query .= " ORDER BY full_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($clients);
?>

<div class="client-summary">
    <strong>Total Assigned Clients:</strong> <?= $total ?>
</div>

<!-- CLIENT TABLE -->
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
            <th>Chat</th>
            <th>History</th>
        </tr>
    </thead>
    <tbody>

    <?php if ($total == 0): ?>
        <tr><td colspan="8" style="text-align:center;">No clients found</td></tr>
    <?php endif; ?>

    <?php foreach ($clients as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['account_number']) ?></td>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['district']) ?></td>
            <td><?= htmlspecialchars($c['barangay']) ?></td>
            <td><?= htmlspecialchars($c['date_installed']) ?></td>

            <!-- OPEN CURRENT CHAT -->
            <td>
                <a href="../dashboard/csr_dashboard.php?tab=chat&client=<?= $c['id'] ?>" 
                   class="chat-btn">
                   💬 Chat
                </a>
            </td>

            <!-- NEW: HISTORY PAGE -->
            <td>
                <a href="../chat/history_list.php?client=<?= $c['id'] ?>" 
                   class="history-btn">
                   📜 History
                </a>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
</div>
