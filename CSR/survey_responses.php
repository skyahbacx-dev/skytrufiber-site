<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Logo
$logoPath = file_exists("AHBALOGO.png") ? "AHBALOGO.png" : "../SKYTRUFIBER/AHBALOGO.png";

// Pagination
$limit = 10;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Search & sorting
$search = $_GET['search'] ?? '';
$sort   = $_GET['sort'] ?? 'created_at';
$dir    = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';
$allowed = ["client_name", "account_number", "district", "location", "feedback", "created_at"];
if (!in_array($sort, $allowed)) $sort = "created_at";

// Query condition
$where = "";
$params = [];
if ($search !== '') {
    $where = "WHERE client_name ILIKE :s OR account_number ILIKE :s OR district ILIKE :s OR location ILIKE :s OR email ILIKE :s";
    $params[':s'] = "%$search%";
}

// Count total
$stmt = $conn->prepare("SELECT COUNT(*) FROM survey_responses $where");
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch data
$query = "
    SELECT id, client_name, account_number, district, location, email, feedback, created_at
    FROM survey_responses
    $where
    ORDER BY $sort $dir
";
$stmt = $conn->prepare($query . " LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================
   EXPORT HANDLERS
===================== */
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $exportStmt = $conn->prepare($query);
    $exportStmt->execute($params);
    $exportData = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV Export
    if ($exportType === 'csv') {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=survey_responses.csv");
        $out = fopen("php://output", "w");
        fputcsv($out, ["Client Name", "Account #", "District", "Location", "Email", "Feedback", "Date Installed"]);
        foreach ($exportData as $r) {
            fputcsv($out, [
                $r['client_name'],
                $r['account_number'],
                $r['district'],
                $r['location'],
                $r['email'],
                $r['feedback'],
                date('Y-m-d', strtotime($r['created_at']))
            ]);
        }
        fclose($out);
        exit;
    }

    // Excel Export
    if ($exportType === 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=survey_responses.xls");
        echo "Client Name\tAccount #\tDistrict\tLocation\tEmail\tFeedback\tDate Installed\n";
        foreach ($exportData as $r) {
            echo "{$r['client_name']}\t{$r['account_number']}\t{$r['district']}\t{$r['location']}\t{$r['email']}\t{$r['feedback']}\t" . date('Y-m-d', strtotime($r['created_at'])) . "\n";
        }
        exit;
    }

    // PDF Export
    if ($exportType === 'pdf') {
        require_once('../vendor/autoload.php');
        $html = "<h2>Survey Responses</h2>
                 <table border='1' cellspacing='0' cellpadding='5'>
                    <thead><tr>
                        <th>Client Name</th><th>Account #</th><th>District</th><th>Location</th><th>Email</th><th>Feedback</th><th>Date Installed</th>
                    </tr></thead><tbody>";
        foreach ($exportData as $r) {
            $html .= "<tr>
                <td>{$r['client_name']}</td>
                <td>{$r['account_number']}</td>
                <td>{$r['district']}</td>
                <td>{$r['location']}</td>
                <td>{$r['email']}</td>
                <td>{$r['feedback']}</td>
                <td>" . date('Y-m-d', strtotime($r['created_at'])) . "</td>
            </tr>";
        }
        $html .= "</tbody></table>";

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($html);
        $mpdf->Output("survey_responses.pdf", "D");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey Responses</title>
<link rel="stylesheet" href="survey_responses.css">
</head>

<body>
<!-- HEADER -->
<header>
    <button id="hamb" onclick="toggleSidebar()">☰</button>
    <div class="brand">
        <img src="<?= $logoPath ?>" alt="Logo">
        <span>CSR Dashboard — Survey Responses</span>
    </div>
</header>

<!-- SIDEBAR -->
<div id="overlay" onclick="toggleSidebar(false)"></div>
<aside id="sidebar">
    <h2>CSR Menu</h2>
    <a href="csr_dashboard.php">💬 Chat Dashboard</a>
    <a href="csr_dashboard.php?tab=mine">👤 My Clients</a>
    <a href="csr_dashboard.php?tab=rem">⏰ Reminders</a>
    <a href="survey_responses.php" class="active">📝 Survey Responses</a>
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</aside>

<!-- MAIN CONTENT -->
<main>
    <div class="page-header">
        <h1>📝 Survey Responses</h1>
        <form method="GET" class="search-box">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, account, etc.">
            <button type="submit" class="btn-search">Search</button>
            <button type="submit" name="export" value="csv" class="btn-export">CSV</button>
            <button type="submit" name="export" value="excel" class="btn-export">Excel</button>
            <button type="submit" name="export" value="pdf" class="btn-export">PDF</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th onclick="sortBy('client_name')">Client Name</th>
                    <th onclick="sortBy('account_number')">Account #</th>
                    <th onclick="sortBy('district')">District</th>
                    <th onclick="sortBy('location')">Location</th>
                    <th onclick="sortBy('email')">Email</th>
                    <th onclick="sortBy('feedback')">Feedback</th>
                    <th onclick="sortBy('created_at')">Date Installed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['client_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['account_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['district'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['location'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['feedback'] ?? '') ?></td>
                        <td><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>" 
               class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</main>

<script>
function toggleSidebar(force = null) {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const active = force === null ? !sidebar.classList.contains("active") : force;
    sidebar.classList.toggle("active", active);
    overlay.style.display = active ? "block" : "none";
}

function sortBy(column) {
    const url = new URL(window.location.href);
    url.searchParams.set("sort", column);
    const dir = url.searchParams.get("dir") === "ASC" ? "DESC" : "ASC";
    url.searchParams.set("dir", dir);
    window.location.href = url.toString();
}
</script>
</body>
</html>
