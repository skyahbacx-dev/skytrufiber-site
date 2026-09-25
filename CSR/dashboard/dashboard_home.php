<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: /csr");
    exit;
}

require __DIR__ . "/../../db_connect.php";

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;

/* ------------------------------------------------------------
   Detect the assigned-CSR column on tickets/users the same
   defensive way my_clients.php does, since the live schema has
   drifted from the reference schema.sql dump.
------------------------------------------------------------ */
function sky_detect_column(PDO $conn, string $table, array $candidates): ?string {
    $placeholders = "'" . implode("','", array_map('addslashes', $candidates)) . "'";
    try {
        $stmt = $conn->query("
            SELECT column_name FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = " . $conn->quote($table) . "
              AND column_name IN ($placeholders) LIMIT 1
        ");
        return $stmt->fetch(PDO::FETCH_COLUMN) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

$ticketAssignedCol = sky_detect_column($conn, 'tickets', ['assigned_csr', 'assigned_csr_user', 'assigned_to', 'assigned']);

/* ------------------------------------------------------------
   Today's Work stats (scoped to this CSR when we can tell)
------------------------------------------------------------ */
$statusCounts = ['unresolved' => 0, 'pending' => 0, 'resolved' => 0];
try {
    $sql = $ticketAssignedCol
        ? "SELECT status, COUNT(*) AS total FROM tickets WHERE {$ticketAssignedCol} = :csr GROUP BY status"
        : "SELECT status, COUNT(*) AS total FROM tickets GROUP BY status";
    $stmt = $conn->prepare($sql);
    $stmt->execute($ticketAssignedCol ? [':csr' => $csrUser] : []);
    foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $status => $total) {
        $statusCounts[strtolower($status)] = (int)$total;
    }
} catch (Exception $e) { /* leave zeros */ }

$totalHandled = array_sum($statusCounts);
$completionRate = $totalHandled > 0 ? round(($statusCounts['resolved'] / $totalHandled) * 100) : 0;

/* Survey stats: real average now that rating exists (may be all-NULL until
   customers start using the updated form - COALESCE keeps this safe). */
$surveyStats = ['total' => 0, 'avg_rating' => null];
try {
    $row = $conn->query("SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM survey_responses")
                ->fetch(PDO::FETCH_ASSOC);
    $surveyStats['total'] = (int)($row['total'] ?? 0);
    $surveyStats['avg_rating'] = $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : null;
} catch (Exception $e) { /* leave defaults */ }

/* ------------------------------------------------------------
   Customer queue: assigned customers who are waiting or in
   follow-up, most urgent first.
------------------------------------------------------------ */
$queue = [];
try {
    $sql = "
        SELECT u.id, u.full_name, u.account_number,
               COALESCE(u.ticket_status, 'unresolved') AS ticket_status,
               (SELECT id FROM survey_responses WHERE user_id = u.id OR account_number = u.account_number ORDER BY created_at DESC LIMIT 1) AS latest_survey_id
        FROM users u
        WHERE u.assigned_csr = :csr
          AND COALESCE(u.ticket_status, 'unresolved') != 'resolved'
        ORDER BY
          CASE COALESCE(u.ticket_status, 'unresolved')
            WHEN 'pending' THEN 0
            WHEN 'unresolved' THEN 1
            ELSE 2
          END,
          u.id DESC
        LIMIT 8
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':csr' => $csrUser]);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* leave empty - table shows an empty state */ }

function sky_status_badge(string $status): string {
    $status = strtolower($status);
    $map = [
        'pending'    => ['warning', 'Waiting'],
        'unresolved' => ['danger',  'Follow-up'],
        'resolved'   => ['success', 'Resolved'],
    ];
    [$style, $label] = $map[$status] ?? ['neutral', ucfirst($status)];
    return "<span class=\"sky-badge sky-badge-{$style}\">{$label}</span>";
}

/* Initials + a stable color pick for the avatar bubble, so the same
   customer always gets the same color (based on their id, not random). */
function sky_avatar(string $name, int $seed): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(($parts[0][0] ?? '?') . ($parts[1][0] ?? ''));
    $variant = $seed % 5;
    return "<div class=\"sky-avatar sky-avatar-{$variant}\">" . htmlspecialchars($initials) . "</div>";
}
?>
<div class="sky-page-header">
    <div>
        <h1>Welcome back, <?= htmlspecialchars(explode(' ', $csrFullName)[0]) ?> 👋</h1>
        <p>Here's what needs your attention today.</p>
    </div>
</div>

<div class="sky-card-grid" style="margin-bottom: 24px;">
    <div class="sky-card sky-stat-card">
        <div class="sky-stat-label">Waiting</div>
        <div class="sky-stat-value"><?= $statusCounts['pending'] ?></div>
        <div class="sky-stat-sub">Customers waiting on a reply</div>
    </div>
    <div class="sky-card sky-stat-card">
        <div class="sky-stat-label">Follow-up</div>
        <div class="sky-stat-value"><?= $statusCounts['unresolved'] ?></div>
        <div class="sky-stat-sub">Open, unresolved tickets</div>
    </div>
    <div class="sky-card sky-stat-card">
        <div class="sky-stat-label">Completion Rate</div>
        <div class="sky-stat-value"><?= $completionRate ?>%</div>
        <div class="sky-stat-sub"><?= $statusCounts['resolved'] ?> of <?= $totalHandled ?> resolved</div>
    </div>
    <div class="sky-card sky-stat-card">
        <div class="sky-stat-label">Avg. Survey Rating</div>
        <div class="sky-stat-value"><?= $surveyStats['avg_rating'] !== null ? $surveyStats['avg_rating'] . ' / 5' : '—' ?></div>
        <div class="sky-stat-sub"><?= $surveyStats['total'] ?> responses total</div>
    </div>
</div>

<div class="sky-page-header">
    <div><h1 style="font-size:16px;">Customer Queue</h1></div>
    <button class="sky-btn sky-btn-secondary sky-btn-sm" onclick="Sky.goTab('customers')">View all customers →</button>
</div>

<div class="sky-card" style="padding:0;">
<?php if (empty($queue)): ?>
    <div class="sky-empty">
        <div class="sky-empty__icon">✅</div>
        <div>No customers waiting on you right now.</div>
    </div>
<?php else: ?>
    <?php foreach ($queue as $c): ?>
        <div class="sky-queue-row">
            <?= sky_avatar($c['full_name'] ?? '?', (int)$c['id']) ?>
            <div class="sky-queue-row__main">
                <div class="sky-queue-row__name"><?= htmlspecialchars($c['full_name'] ?? 'Unknown') ?></div>
                <div class="sky-queue-row__meta">Account #<?= htmlspecialchars($c['account_number'] ?? '—') ?></div>
            </div>
            <div class="sky-queue-row__badges">
                <?= sky_status_badge($c['ticket_status']) ?>
                <?= $c['latest_survey_id'] ? '<span class="sky-badge sky-badge-info">Survey ✔</span>' : '<span class="sky-badge sky-badge-neutral">No survey</span>' ?>
            </div>
            <button class="sky-btn sky-btn-primary sky-btn-sm" onclick="Sky.goTab('customer', {id: <?= (int)$c['id'] ?>})">Open</button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>