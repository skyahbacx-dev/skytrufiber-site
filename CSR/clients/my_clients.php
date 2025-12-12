<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: /csr");
    exit;
}

require __DIR__ . "/../../db_connect.php";

$csrUser = $_SESSION["csr_user"];
$search  = trim($_GET["search"] ?? "");

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

/* ============================================================
   GET TODAY'S TICKET COUNTS
============================================================ */
$today = date("Y-m-d");

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
$stats->execute([
    ":csr" => $csrUser,
    ":today" => $today
]);
$counts = $stats->fetch(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/CSR/clients/my_clients.css">

<h1>👥 My Clients</h1>

<!-- SEARCH BAR -->
<form method="GET" class="search-bar">
    <input type="hidden" name="tab" value="clients">
    <input type="text" name="search" placeholder="Search clients..."
           value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
</form>

<!-- SUMMARY BOXES -->
<div class="summary-container">
    <div class="summary-box total">Total Clients: <strong><?= $total ?></strong></div>
    <div class="summary-box unresolved">Unresolved Today: <strong><?= $counts['unresolved'] ?></strong></div>
    <div class="summary-box pending">Pending Today: <strong><?= $counts['pending'] ?></strong></div>
    <div class="summary-box resolved">Resolved Today: <strong><?= $counts['resolved'] ?></strong></div>
</div>

<!-- EXPORT DAILY TICKET REPORT -->
<div class="export-panel">
    <a href="/CSR/clients/print_daily_report.php" class="export-btn" target="_blank">
        🖨 Print Daily Ticket Summary
    </a>
</div>

<script>
// --- ENCRYPTED ROUTE GENERATOR ---
function enc(route) {
    return "/home.php?v=" + btoa(route + "|" + Date.now());
}

// --- Open dashboard with correct tab + client ID ---
function openChat(clientID) {
    window.location.href = enc("csr_chat") + "&client_id=" + clientID;
}

function openHistory(clientID) {
    window.location.href = enc("csr_clients") + "&client=" + clientID;
}
</script>

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

            <!-- OPEN CHAT (Encrypted) -->
            <td>
                <button class="chat-btn" onclick="openChat(<?= $c['id'] ?>)">
                    💬 Chat
                </button>
            </td>

            <!-- OPEN HISTORY (Encrypted) -->
            <td>
                <button class="history-btn" onclick="openHistory(<?= $c['id'] ?>)">
                    📜 History
                </button>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
</div>
