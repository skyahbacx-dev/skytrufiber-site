<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require __DIR__ . "/../../db_connect.php";

$clientID = intval($_GET["client"] ?? 0);
if ($clientID <= 0) exit("<h2>Invalid client</h2>");

/* ============================================================
   FETCH CLIENT DETAILS
============================================================ */
$stmt = $conn->prepare("SELECT full_name, account_number FROM users WHERE id = ?");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("<h2>Client not found.</h2>");

$clientName = htmlspecialchars($client["full_name"]);
$acctNo     = htmlspecialchars($client["account_number"]);

/* ============================================================
   FILTERS & SEARCH
============================================================ */
$search = trim($_GET["search"] ?? "");
$sort   = $_GET["sort"] ?? "newest";

/* Build base query and params */
$query = "SELECT id, status, created_at FROM tickets WHERE client_id = :cid";
$params = [":cid" => $clientID];

if ($search !== "") {
    $query .= " AND (CAST(id AS TEXT) ILIKE :s OR status ILIKE :s)";
    $params[":s"] = "%$search%";
}

/* SORT / FILTER */
switch ($sort) {
    case "oldest":
        $orderSQL = " ORDER BY created_at ASC";
        break;
    case "resolved":
        $orderSQL = " AND status = 'resolved' ORDER BY created_at DESC";
        break;
    case "unresolved":
        $orderSQL = " AND status != 'resolved' ORDER BY created_at DESC";
        break;
    default:
        $orderSQL = " ORDER BY created_at DESC";
        $sort = "newest";
        break;
}

$query .= $orderSQL;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Create encrypted base token for CSR clients route so links match home.php routing */
$token_clients = urlencode(base64_encode("csr_clients|" . time()));
?>

<!-- Correct CSS/JS paths for dashboard wrapper -->
<link rel="stylesheet" href="/CSR/history/history.css?v=<?= time(); ?>">
<script src="/CSR/history/history.js?v=<?= time(); ?>"></script>

<div class="history-container">

    <h2 class="history-title">📜 Ticket History — <?= $clientName ?> (<?= $acctNo ?>)</h2>

    <a href="/home.php?v=<?= $token_clients ?>" class="back-btn">← Back to My Clients</a>

    <!-- SEARCH + SORT CONTROLS -->
    <div class="history-controls">

        <form method="GET" class="history-search" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="client" value="<?= $clientID ?>">
            <input type="text" name="search" placeholder="Search tickets..."
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>

        <div class="sort-tabs" style="margin-left:auto;">
            <?php
                // preserve search and client when building sort links
                $qs_base = "client=" . $clientID . ($search !== "" ? "&search=" . urlencode($search) : "");
            ?>
            <a href="?<?= $qs_base ?>&sort=newest" class="<?= $sort=='newest' ? 'active' : '' ?>">Newest</a>
            <a href="?<?= $qs_base ?>&sort=oldest" class="<?= $sort=='oldest' ? 'active' : '' ?>">Oldest</a>
            <a href="?<?= $qs_base ?>&sort=resolved" class="<?= $sort=='resolved' ? 'active' : '' ?>">Resolved</a>
            <a href="?<?= $qs_base ?>&sort=unresolved" class="<?= $sort=='unresolved' ? 'active' : '' ?>">Unresolved</a>
        </div>

    </div>

    <!-- JUMP BUTTONS -->
    <div style="margin-top:12px;margin-bottom:8px;">
        <button id="jumpTop" class="jump-btn">⬆ Top</button>
        <button id="jumpBottom" class="jump-btn">⬇ Bottom</button>
    </div>

    <!-- TICKET LIST -->
    <div class="history-list" id="ticketList">

        <?php if (!$tickets): ?>
            <div class="empty">No tickets found.</div>

        <?php else:

            $lastMonth = "";

            foreach ($tickets as $t):

                $month = date("F Y", strtotime($t["created_at"]));

                if ($month !== $lastMonth):
                    echo "<div class='month-header'>— " . htmlspecialchars($month) . " —</div>";
                    $lastMonth = $month;
                endif;

                $statusClass = strtolower($t["status"] ?? "unresolved");
                $statusLabel = strtoupper(htmlspecialchars($t["status"] ?? "UNRESOLVED"));

                // build encrypted link for this ticket (route csr_clients) and append ticket param
                $link = "/home.php?v=" . $token_clients . "&client=" . $clientID . "&ticket=" . intval($t['id']);
        ?>

            <a class="ticket-item" href="<?= htmlspecialchars($link) ?>">
                <div class="ticket-left">
                    <div class="ticket-title">Ticket #<?= intval($t["id"]) ?></div>
                    <div class="ticket-date"><?= date("M j, Y g:i A", strtotime($t["created_at"])) ?></div>
                </div>

                <div class="ticket-right">
                    <div class="ticket-status <?= htmlspecialchars($statusClass) ?>">
                        <?= $statusLabel ?>
                    </div>
                </div>
            </a>

        <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<!-- Small inline JS to wire jump buttons (works even if history.js is not loaded) -->
<script>
document.getElementById("jumpTop").addEventListener("click", function(){
    document.getElementById("ticketList").scrollTo({ top: 0, behavior: "smooth" });
});
document.getElementById("jumpBottom").addEventListener("click", function(){
    const el = document.getElementById("ticketList");
    el.scrollTo({ top: el.scrollHeight, behavior: "smooth" });
});
</script>
