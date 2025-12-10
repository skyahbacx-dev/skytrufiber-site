<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$ticketID = intval($_GET["ticket"] ?? 0);
if ($ticketID <= 0) {
    exit("<h2>Invalid ticket ID.</h2>");
}

/* ============================================================
   FETCH TICKET INFO
============================================================ */
$t = $conn->prepare("
    SELECT 
        t.id,
        t.client_id,
        t.status,
        t.created_at,
        u.full_name,
        u.account_number
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
");
$t->execute([$ticketID]);
$row = $t->fetch(PDO::FETCH_ASSOC);

if (!$row) exit("<h2>Ticket not found.</h2>");

$clientID   = $row["client_id"];
$clientName = htmlspecialchars($row["full_name"]);
$acctNo     = htmlspecialchars($row["account_number"]);
$status     = strtolower($row["status"]);
$createdAt  = date("M j, Y g:i A", strtotime($row["created_at"]));

/* ============================================================
   FETCH CHAT MESSAGES
============================================================ */
$msgs = $conn->prepare("
    SELECT 
        id,
        sender_type,
        message,
        deleted,
        edited,
        created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$msgs->execute([$ticketID]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH TICKET LOGS
============================================================ */
$logs = $conn->prepare("
    SELECT action, csr_user, timestamp
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$logs->execute([$clientID]);
$logRows = $logs->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="history.css">

<div class="history-wrapper">

<h2 class="history-title">📄 Ticket #<?= $ticketID ?> — <?= strtoupper($status) ?></h2>

<a href="../dashboard/csr_dashboard.php?tab=clients&client=<?= $clientID ?>" class="back-btn">
    ← Back to Ticket History
</a>

<p class="history-sub"><strong>Client:</strong> <?= $clientName ?> (<?= $acctNo ?>)</p>
<p class="history-sub"><strong>Created:</strong> <?= $createdAt ?></p>

<!-- ============================================================
     SORTING TABS
============================================================ -->
<div class="tab-bar">
    <button class="tab-btn active" data-tab="timeline">📌 Timeline</button>
    <button class="tab-btn" data-tab="chat">💬 Chat Messages</button>
    <button class="tab-btn" data-tab="info">ℹ Ticket Info</button>
</div>

<!-- ============================================================
     CONTENT SECTIONS
============================================================ -->

<!-- ===== TIMELINE TAB ===== -->
<div id="tab-timeline" class="tab-section">

    <h3 class="section-title">📌 Ticket Timeline</h3>

    <div class="timeline">
    <?php if (!$logRows): ?>
        <div class="empty">No timeline logs found.</div>
    <?php else: ?>
        <?php foreach ($logRows as $log): 
            $action = strtolower($log["action"]);
        ?>
            <div class="log-entry action-<?= $action ?>">
                <div class="log-action"><?= strtoupper($log["action"]) ?></div>
                <div class="log-by">by <?= htmlspecialchars($log["csr_user"]) ?></div>
                <div class="log-time"><?= date("M j, Y g:i A", strtotime($log["timestamp"])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

</div>

<!-- ===== CHAT TAB ===== -->
<div id="tab-chat" class="tab-section hidden">

    <h3 class="section-title">💬 Chat Messages</h3>

    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="csr">CSR</button>
        <button class="filter-btn" data-filter="client">Client</button>
        <button class="filter-btn" data-filter="deleted">Deleted</button>
    </div>

    <div class="chat-history" id="chatContainer">
    <?php foreach ($messages as $m): ?>
        <div class="chat-msg <?= $m['sender_type'] ?> <?= $m['deleted']?'deleted-msg':'' ?>" 
             data-type="<?= $m['sender_type'] ?>" 
             data-deleted="<?= $m['deleted'] ?>">

            <div class="bubble">
                <?php if ($m["deleted"]): ?>
                    <i>🗑️ Message deleted</i>
                <?php else: ?>
                    <?= nl2br(htmlspecialchars($m["message"])) ?>
                <?php endif; ?>
            </div>

            <div class="meta">
                <?= date("M j, Y g:i A", strtotime($m["created_at"])) ?>
                <?php if ($m["edited"]): ?>
                    <span class="edited">(edited)</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- ===== INFO TAB ===== -->
<div id="tab-info" class="tab-section hidden">

    <h3 class="section-title">ℹ Ticket Information</h3>

    <div class="info-box">
        <p><strong>Client Name:</strong> <?= $clientName ?></p>
        <p><strong>Account #:</strong> <?= $acctNo ?></p>
        <p><strong>Status:</strong> <?= strtoupper($status) ?></p>
        <p><strong>Created:</strong> <?= $createdAt ?></p>
    </div>

</div>

<!-- ============================================================
     JUMP BUTTONS
============================================================ -->
<button id="jumpTop" class="jump-btn">🔼</button>
<button id="jumpBottom" class="jump-btn">🔽</button>

</div> <!-- end wrapper -->

<script>
// ===================== TABS =====================
document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const tab = btn.dataset.tab;
        document.querySelectorAll(".tab-section").forEach(s => s.classList.add("hidden"));
        document.getElementById("tab-" + tab).classList.remove("hidden");
    });
});

// ===================== FILTER CHAT =====================
document.querySelectorAll(".filter-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const filter = btn.dataset.filter;
        document.querySelectorAll(".chat-msg").forEach(msg => {
            let show = false;
            if (filter === "all") show = true;
            else if (filter === "csr" && msg.dataset.type === "csr") show = true;
            else if (filter === "client" && msg.dataset.type !== "csr") show = true;
            else if (filter === "deleted" && msg.dataset.deleted == "1") show = true;

            msg.style.display = show ? "block" : "none";
        });
    });
});

// ===================== JUMP BUTTONS =====================
document.getElementById("jumpTop").onclick = () => window.scrollTo({ top: 0, behavior: "smooth" });
document.getElementById("jumpBottom").onclick = () => window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
</script>
