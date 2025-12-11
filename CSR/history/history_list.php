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

$query = "SELECT id, status, created_at FROM tickets WHERE client_id = :cid";
$params = [":cid" => $clientID];

if ($search !== "") {
    $query .= " AND (
        CAST(id AS TEXT) ILIKE :s OR 
        status ILIKE :s
    )";
    $params[":s"] = "%$search%";
}

/* SORT CONDITIONS */
$orderSQL = match($sort) {
    "oldest"     => " ORDER BY created_at ASC",
    "resolved"   => " AND status = 'resolved' ORDER BY created_at DESC",
    "unresolved" => " AND status != 'resolved' ORDER BY created_at DESC",
    default      => " ORDER BY created_at DESC",
};

$query .= $orderSQL;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- LOAD HISTORY CSS & JS (correct paths for csr_dashboard.php wrapper) -->
<link rel="stylesheet" href="../history/history.css?v=<?= time(); ?>">
<script src="../history/history.js?v=<?= time(); ?>"></script>

<div class="history-container">

    <h2 class="history-title">📜 Ticket History — <?= $clientName ?> (<?= $acctNo ?>)</h2>

    <a href="../dashboard/csr_dashboard.php?tab=clients" class="back-btn">
        ← Back to My Clients
    </a>

    <!-- SEARCH + SORT CONTROLS -->
    <div class="history-controls">

        <form class="history-search">
            <input type="hidden" name="client" value="<?= $clientID ?>">
            <input type="text" name="search" placeholder="Search tickets..."
                   value="<?= htmlspecialchars($search) ?>">
        </form>

        <div class="sort-tabs">
            <a href="?client=<?= $clientID ?>&sort=newest" 
               class="<?= $sort=='newest' ? 'active' : '' ?>">Newest</a>

            <a href="?client=<?= $clientID ?>&sort=oldest" 
               class="<?= $sort=='oldest' ? 'active' : '' ?>">Oldest</a>

            <a href="?client=<?= $clientID ?>&sort=resolved" 
               class="<?= $sort=='resolved' ? 'active' : '' ?>">Resolved</a>

            <a href="?client=<?= $clientID ?>&sort=unresolved" 
               class="<?= $sort=='unresolved' ? 'active' : '' ?>">Unresolved</a>
        </div>

    </div>

    <!-- JUMP BUTTONS -->
    <button id="jumpTop" class="jump-btn">⬆ Top</button>
    <button id="jumpBottom" class="jump-btn">⬇ Bottom</button>

    <!-- TICKET LIST -->
    <div class="history-list" id="ticketList">

        <?php if (!$tickets): ?>
            <div class="empty">No tickets found.</div>

        <?php else:

            $lastMonth = "";

            foreach ($tickets as $t):

                $month = date("F Y", strtotime($t["created_at"]));

                if ($month !== $lastMonth):
                    echo "<div class='month-header'>— $month —</div>";
                    $lastMonth = $month;
                endif;

                $statusClass = strtolower($t["status"]);
        ?>

            <a class="ticket-item"
               href="../dashboard/csr_dashboard.php?tab=clients&ticket=<?= $t['id'] ?>">

                <div class="ticket-title">Ticket #<?= $t["id"] ?></div>

                <div class="ticket-status <?= $statusClass ?>">
                    <?= strtoupper($t["status"]) ?>
                </div>

                <div class="ticket-date">
                    <?= date("M j, Y g:i A", strtotime($t["created_at"])) ?>
                </div>

            </a>

        <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>
