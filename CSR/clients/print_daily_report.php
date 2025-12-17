<?php
ini_set("session.name", "CSRSESSID");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csr_user'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Unauthorized access");
}

require __DIR__ . "/../../db_connect.php";


$csrUser   = $_SESSION["csr_user"];
$todayDate = date("Y-m-d");

/* ============================================================
   FETCH TODAY'S TICKETS
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        t.id AS ticket_id,
        t.status,
        t.created_at,
        u.full_name,
        u.account_number,
        u.district,
        u.barangay
    FROM tickets t
    LEFT JOIN users u ON u.id = t.client_id
    WHERE DATE(t.created_at) = :today
    ORDER BY t.created_at DESC
");
$stmt->execute([":today" => $todayDate]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* COUNT SUMMARY */
$total = count($tickets);
$unresolved = 0;
$pending = 0;
$resolved = 0;

foreach ($tickets as $t) {
    switch (strtolower($t["status"])) {
        case "unresolved": $unresolved++; break;
        case "pending":    $pending++; break;
        case "resolved":   $resolved++; break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daily Ticket Report - <?= $todayDate ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
        }

        .logo {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo img {
            width: 160px;
        }

        h2 {
            text-align: center;
            margin: 8px 0 20px;
        }

        /* Summary Boxes */
        .summary-container {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .summary-box {
            padding: 12px 20px;
            border-radius: 6px;
            color: #fff;
            font-weight: bold;
            min-width: 160px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .total { background: #007bff; }
        .unresolved { background: #d9534f; }
        .pending { background: #f0ad4e; }
        .resolved { background: #5cb85c; }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 14px;
        }

        th {
            background: #05702e;
            color: #fff;
            padding: 10px;
            text-align: left;
        }

        td {
            padding: 9px;
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) td {
            background: #f8f8f8;
        }

        /* Footer note */
        .footnote {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }

        @media print {
            #printBtn { display: none; }
        }
    </style>

</head>
<body>

    <!-- LOGO -->
    <div class="logo">
        <img src="/SKYTRUFIBER.png" alt="AHBA Logo">
    </div>

    <h2>Daily Ticket Report — <?= date("F d, Y") ?></h2>

    <!-- SUMMARY -->
    <div class="summary-container">
        <div class="summary-box total">Total Tickets: <?= $total ?></div>
        <div class="summary-box unresolved">Unresolved: <?= $unresolved ?></div>
        <div class="summary-box pending">Pending: <?= $pending ?></div>
        <div class="summary-box resolved">Resolved: <?= $resolved ?></div>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th>Ticket #</th>
                <th>Client</th>
                <th>Account #</th>
                <th>District</th>
                <th>Barangay</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($total == 0): ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:20px; color:#777;">
                    No tickets were created today.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
            <tr>
                <td>#<?= $t["ticket_id"] ?></td>
                <td><?= htmlspecialchars($t["full_name"]) ?></td>
                <td><?= htmlspecialchars($t["account_number"]) ?></td>
                <td><?= htmlspecialchars($t["district"]) ?></td>
                <td><?= htmlspecialchars($t["barangay"]) ?></td>
                <td><?= strtoupper($t["status"]) ?></td>
                <td><?= date("M d, Y g:i A", strtotime($t["created_at"])) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <button id="printBtn" onclick="window.print()" 
            style="margin-top:20px;padding:10px 18px;background:#05702e;color:#fff;border:none;border-radius:6px;cursor:pointer;">
        Print Report
    </button>

    <div class="footnote">
        This report was auto-generated by the SkyTruFiber CSR System.
    </div>

</body>

<script>
// Auto-open print dialog when page loads
window.onload = () => { window.print(); };
</script>

</html>
