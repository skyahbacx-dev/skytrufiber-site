<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: /csr");
    exit;
}

require __DIR__ . "/../../db_connect.php";

$csrUser  = $_SESSION["csr_user"];
$clientID = intval($_GET["id"] ?? 0);

if ($clientID <= 0) {
    echo '<div class="sky-empty"><div class="sky-empty__icon">🔍</div>No customer selected.</div>';
    return;
}

/* ------------------------------------------------------------
   CUSTOMER + LATEST TICKET
------------------------------------------------------------ */
$stmt = $conn->prepare("
    SELECT u.*,
           (SELECT id FROM tickets WHERE client_id = u.id ORDER BY id DESC LIMIT 1) AS latest_ticket_id,
           (SELECT status FROM tickets WHERE client_id = u.id ORDER BY id DESC LIMIT 1) AS latest_ticket_status
    FROM users u
    WHERE u.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $clientID]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo '<div class="sky-empty"><div class="sky-empty__icon">⚠️</div>Customer not found.</div>';
    return;
}

$activeTicketId = (int)($customer['latest_ticket_id'] ?? 0);
$ticketStatus   = strtolower($customer['latest_ticket_status'] ?? 'unresolved');

function sky_ws_badge(string $status): string {
    $status = strtolower($status);
    $map = [
        'pending'    => ['warning', 'Waiting'],
        'unresolved' => ['danger',  'Follow-up'],
        'resolved'   => ['success', 'Resolved'],
    ];
    [$style, $label] = $map[$status] ?? ['neutral', ucfirst($status)];
    return "<span class=\"sky-badge sky-badge-{$style}\">{$label}</span>";
}

/* ------------------------------------------------------------
   SURVEY RESPONSES — match by user_id when present (new signups,
   now that register.php links it), falling back to account_number
   for older rows that predate that fix.
------------------------------------------------------------ */
$stmt = $conn->prepare("
    SELECT id, feedback, rating, district, location, created_at
    FROM survey_responses
    WHERE user_id = :id OR account_number = :acc
    ORDER BY created_at DESC
");
$stmt->execute([':id' => $clientID, ':acc' => $customer['account_number']]);
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ratedSurveys = array_filter($surveys, fn($s) => $s['rating'] !== null);
$avgRating = count($ratedSurveys)
    ? round(array_sum(array_column($ratedSurveys, 'rating')) / count($ratedSurveys), 1)
    : null;

/* ------------------------------------------------------------
   TICKET HISTORY (all tickets for this customer)
------------------------------------------------------------ */
$stmt = $conn->prepare("SELECT id, status, created_at FROM tickets WHERE client_id = :id ORDER BY created_at DESC");
$stmt->execute([':id' => $clientID]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$emojiFace = [1 => '😡', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😍'];
?>
<link rel="stylesheet" href="/CSR/chat/chat.css?v=3">

<div class="sky-page-header">
    <div>
        <h1><?= htmlspecialchars($customer['full_name'] ?? 'Unknown') ?> <?= sky_ws_badge($ticketStatus) ?></h1>
        <p>Account #<?= htmlspecialchars($customer['account_number'] ?? '—') ?></p>
    </div>
    <button class="sky-btn sky-btn-secondary sky-btn-sm" onclick="Sky.goTab('customers')">← Back to Customers</button>
</div>

<div class="sky-tabs" data-tab-group>
    <button class="sky-tab active" data-tab="overview">Overview</button>
    <button class="sky-tab" data-tab="conversation">Conversation</button>
    <button class="sky-tab" data-tab="survey">Survey <?= count($surveys) ? "(" . count($surveys) . ")" : "" ?></button>
    <button class="sky-tab" data-tab="history">History <?= count($tickets) ? "(" . count($tickets) . ")" : "" ?></button>
</div>

<!-- ============ OVERVIEW ============ -->
<div data-tab-panel="overview">
    <div class="sky-card-grid">
        <div class="sky-card">
            <div class="sky-stat-label">Contact</div>
            <p style="margin:8px 0 0;font-size:14px;">
                📧 <?= htmlspecialchars($customer['email'] ?? '—') ?><br>
                📍 <?= htmlspecialchars($customer['barangay'] ?? '—') ?>, <?= htmlspecialchars($customer['district'] ?? '—') ?>
            </p>
        </div>
        <div class="sky-card">
            <div class="sky-stat-label">Account</div>
            <p style="margin:8px 0 0;font-size:14px;">
                Installed: <?= htmlspecialchars($customer['date_installed'] ?? '—') ?><br>
                Assigned CSR: <?= htmlspecialchars($customer['assigned_csr'] ?? 'Unassigned') ?>
            </p>
        </div>
        <div class="sky-card">
            <div class="sky-stat-label">Survey</div>
            <p style="margin:8px 0 0;font-size:14px;">
                Avg. rating: <?= $avgRating !== null ? "{$avgRating} / 5 " . ($emojiFace[round($avgRating)] ?? '') : '—' ?><br>
                <?= count($surveys) ?> response<?= count($surveys) === 1 ? '' : 's' ?>
            </p>
        </div>
    </div>
</div>

<!-- ============ CONVERSATION ============ -->
<div data-tab-panel="conversation" style="display:none;">
    <?php if (!$activeTicketId): ?>
        <div class="sky-empty">
            <div class="sky-empty__icon">💬</div>
            No ticket/conversation exists for this customer yet.
        </div>
    <?php else: ?>
        <div class="sky-card" style="padding:0; overflow:hidden;">
            <div id="ws-messages" class="chat-messages" style="height:420px; overflow-y:auto; padding:16px;">
                <p style="text-align:center;color:#999;">Loading messages…</p>
            </div>
            <?php if ($ticketStatus !== 'resolved'): ?>
            <div style="display:flex; gap:10px; padding:14px; border-top:1px solid var(--gray-200);">
                <input type="text" id="ws-input" class="sky-input" placeholder="Type a message…">
                <button class="sky-btn sky-btn-primary" onclick="wsSendMessage()">Send</button>
            </div>
            <?php else: ?>
            <div style="padding:12px 16px; color:var(--gray-500); font-size:13px; border-top:1px solid var(--gray-200);">
                This ticket is resolved — read only.
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============ SURVEY ============ -->
<div data-tab-panel="survey" style="display:none;">
    <?php if (empty($surveys)): ?>
        <div class="sky-empty">
            <div class="sky-empty__icon">📋</div>
            No survey responses from this customer yet.
        </div>
    <?php else: ?>
        <?php foreach ($surveys as $s): ?>
            <div class="sky-card" style="margin-bottom:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:24px;"><?= $s['rating'] ? ($emojiFace[(int)$s['rating']] ?? '') . " {$s['rating']}/5" : '<span class="sky-badge sky-badge-neutral">No rating</span>' ?></span>
                    <span style="color:var(--gray-500); font-size:12px;"><?= htmlspecialchars(date('M j, Y', strtotime($s['created_at']))) ?></span>
                </div>
                <?php if (!empty($s['feedback'])): ?>
                    <p style="margin:10px 0 0; font-size:14px;"><?= nl2br(htmlspecialchars($s['feedback'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ============ HISTORY ============ -->
<div data-tab-panel="history" style="display:none;">
    <div class="sky-table-wrap">
        <?php if (empty($tickets)): ?>
            <div class="sky-empty"><div class="sky-empty__icon">🗂</div>No tickets on record.</div>
        <?php else: ?>
            <table class="sky-table">
                <thead><tr><th>Ticket</th><th>Status</th><th>Opened</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td data-label="Ticket">#<?= (int)$t['id'] ?></td>
                        <td data-label="Status"><?= sky_ws_badge($t['status']) ?></td>
                        <td data-label="Opened"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($t['created_at']))) ?></td>
                        <td data-label="">
                            <a class="sky-btn sky-btn-secondary sky-btn-sm" href="/CSR/history/history_view.php?ticket=<?= (int)$t['id'] ?>" target="_blank">View transcript</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const ticketId = <?= (int)$activeTicketId ?>;
    const clientId = <?= (int)$clientID ?>;
    let loaded = false;

    function loadMessages() {
        if (!ticketId) return;
        $.post("/CSR/chat/load_messages.php", { ticket_id: ticketId }, function (html) {
            const box = document.getElementById("ws-messages");
            if (!box) return;
            box.innerHTML = html;
            box.scrollTop = box.scrollHeight;
        });
    }

    window.wsSendMessage = function () {
        const input = document.getElementById("ws-input");
        const message = input.value.trim();
        if (!message) return;
        input.value = "";
        $.post("/CSR/chat/send_message.php", { client_id: clientId, ticket_id: ticketId, message: message }, function (res) {
            const data = typeof res === "string" ? JSON.parse(res) : res;
            if (data.status === "SESSION_EXPIRED") { Sky.toast("Session expired — please log in again.", "error"); return; }
            if (data.status && data.status !== "ok" && data.status !== "success") { Sky.toast(data.msg || "Could not send message.", "error"); return; }
            loadMessages();
        }).fail(function () { Sky.toast("Could not send message.", "error"); });
    };

    // Load once, then poll gently while the Conversation tab exists on the page
    // (it's simplest to load immediately - the tab is just hidden/shown via CSS).
    loadMessages();
    setInterval(loadMessages, 6000);

    const input = document.getElementById("ws-input");
    if (input) {
        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter") wsSendMessage();
        });
    }
})();
</script>
