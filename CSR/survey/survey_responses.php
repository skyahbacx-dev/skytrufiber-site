<?php
include '../../db_connect.php';

$search = $_GET['search'] ?? '';
$sort   = $_GET['sort'] ?? 'created_at';
$dir    = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';

$allowed = ["client_name", "account_number", "email", "district", "location", "feedback", "created_at"];
if (!in_array($sort, $allowed)) { $sort = "created_at"; }

$where = "";
$params = [];

if ($search !== "") {
    $where = "WHERE client_name ILIKE :s 
              OR account_number ILIKE :s
              OR email ILIKE :s
              OR district ILIKE :s
              OR location ILIKE :s";
    $params[':s'] = "%$search%";
}

/* Pagination */
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$countStmt = $conn->prepare("SELECT COUNT(*) FROM survey_responses $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

/* Main Query */
$query = "
    SELECT id, client_name, account_number, email, district, location, feedback, created_at
    FROM survey_responses
    $where
    ORDER BY $sort $dir
";

$stmt = $conn->prepare($query . " LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Export Handler */
if (isset($_GET['export'])) {
    $export = $_GET['export'];
    $exportStmt = $conn->prepare($query);
    $exportStmt->execute($params);
    $exportData = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($export === "csv") {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=survey_responses.csv");

        $out = fopen("php://output", "w");
        fputcsv($out, ["Client Name", "Account #", "Email", "District", "Location", "Feedback", "Date Installed"]);

        foreach ($exportData as $r) {
            fputcsv($out, [
                $r['client_name'], $r['account_number'], $r['email'],
                $r['district'], $r['location'], $r['feedback'],
                date("Y-m-d", strtotime($r["created_at"]))
            ]);
        }

        fclose($out);
        exit;
    }

    if ($export === "excel") {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=survey_responses.xls");

        echo "Client Name\tAccount #\tEmail\tDistrict\tLocation\tFeedback\tDate Installed\n";
        foreach ($exportData as $r) {
            echo "{$r['client_name']}\t{$r['account_number']}\t{$r['email']}\t{$r['district']}\t{$r['location']}\t{$r['feedback']}\t" . date("Y-m-d", strtotime($r["created_at"])) . "\n";
        }
        exit;
    }

    if ($export === "pdf") {
        require_once("../../vendor/autoload.php");

        $html = "<h2>Survey Responses</h2>
        <table border='1' cellpadding='5' cellspacing='0'>
        <tr><th>Client Name</th><th>Account #</th><th>Email</th><th>District</th><th>Location</th><th>Feedback</th><th>Date Installed</th></tr>";

        foreach ($exportData as $r) {
            $html .= "<tr>
                <td>{$r['client_name']}</td>
                <td>{$r['account_number']}</td>
                <td>{$r['email']}</td>
                <td>{$r['district']}</td>
                <td>{$r['location']}</td>
                <td>{$r['feedback']}</td>
                <td>" . date("Y-m-d", strtotime($r["created_at"])) . "</td>
            </tr>";
        }

        $html .= "</table>";

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($html);
        $mpdf->Output("survey_responses.pdf", "D");
        exit;
    }
}
?>

<!-- MODULE HTML ONLY (NO <html>, NO <body>) -->

<h1 class="page-title">📋 Survey Responses</h1>

<form method="GET" class="search-section">
    <input type="hidden" name="tab" value="survey"> <!-- keep module active -->
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn">Search</button>
    <button class="btn" name="export" value="csv">CSV</button>
    <button class="btn" name="export" value="excel">Excel</button>
    <button class="btn" name="export" value="pdf">PDF</button>
</form>

<div class="table-wrapper">
<table class="styled-table">
    <thead>
        <tr>
            <th onclick="sortBy('client_name')">Client Name</th>
            <th onclick="sortBy('account_number')">Account #</th>
            <th onclick="sortBy('email')">Email</th>
            <th onclick="sortBy('district')">District</th>
            <th onclick="sortBy('location')">Location</th>
            <th onclick="sortBy('feedback')">Feedback</th>
            <th onclick="sortBy('created_at')">Date Installed</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['client_name']) ?></td>
            <td><?= htmlspecialchars($r['account_number']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['district']) ?></td>
            <td><?= htmlspecialchars($r['location']) ?></td>
            <td><?= htmlspecialchars($r['feedback']) ?></td>
            <td><?= date("Y-m-d", strtotime($r["created_at"])) ?></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</div>

<div class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="page-btn <?= $page == $i ? "active" : "" ?>"
       href="?tab=survey&page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>">
       <?= $i ?>
    </a>
<?php endfor; ?>
</div>

<script>
function sortBy(col) {
    const url = new URL(window.location.href);
    url.searchParams.set("sort", col);

    const current = url.searchParams.get("dir");
    const next = current === "ASC" ? "DESC" : "ASC";
    url.searchParams.set("dir", next);

    url.searchParams.set("tab", "survey"); // keep module active

    window.location.href = url.toString();
}
</script>
