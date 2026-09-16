<?php
// ------------------------------------------------------------
// Use CSR session only — prevents auto logout when client logs in
// ------------------------------------------------------------
ini_set("session.name", "CSRSESSID");
if (!isset($_SESSION)) session_start();

// Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

require __DIR__ . "/../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$filter  = $_POST["filter"] ?? "all"; // all | unresolved | pending | resolved

// If CSR session expired → return silent flag
if (!$csrUser) {
    echo "<div data-session='expired'></div>";
    exit;
}

/* ============================================================
   FETCH CLIENT LIST
============================================================ */
$stmt = $conn->prepare("
    SELECT
        u.id,
        u.full_name,
        u.assigned_csr,
        u.ticket_lock,

        (
            SELECT message
            FROM chat
            WHERE client_id = u.id
              AND deleted IS FALSE
            ORDER BY id DESC
            LIMIT 1
        ) AS last_msg,

        (
            SELECT created_at
            FROM chat
            WHERE client_id = u.id
              AND deleted IS FALSE
            ORDER BY id DESC
            LIMIT 1
        ) AS last_time,

        (
            SELECT status
            FROM tickets
            WHERE client_id = u.id
            ORDER BY id DESC
            LIMIT 1
        ) AS ticket_status

    FROM users u
    ORDER BY u.full_name ASC
");
$stmt->execute();

$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "<p style='padding:15px; color:#888;'>No clients found.</p>";
    exit;
}

/* ============================================================
   FILTER FUNCTION
============================================================ */
function matchFilter($status, $filter)
{
    if ($filter === "all") return true;
    if (!$status) $status = "unresolved";
    return strtolower($status) === strtolower($filter);
}

/* ============================================================
   RENDER LIST
============================================================ */
foreach ($clients as $row):

    $cid        = $row["id"];
    $name       = htmlspecialchars($row["full_name"]);
    $statusRaw  = strtolower($row["ticket_status"] ?? "unresolved");
    $statusUC   = strtoupper($statusRaw);
    $assignedTo = $row["assigned_csr"];
    $isLocked   = ($row["ticket_lock"] ? true : false);

    if (!matchFilter($statusRaw, $filter)) continue;

    /* ========================================================
       STATUS BADGE (CSS-COMPATIBLE)
       ✔ Works with new .status CSS
       ✔ Does NOT break old styling
    ========================================================= */
    $badge = "
        <span class='status {$statusUC}' data-status='{$statusRaw}'>
            {$statusUC}
        </span>
    ";

    /* ========================================================
       ASSIGNMENT ICON
    ========================================================= */
    if (!$assignedTo) {
        $icon = "<button class='assign-btn' data-id='{$cid}'>+</button>";
    } elseif ($assignedTo === $csrUser) {
        $icon = "<button class='unassign-btn' data-id='{$cid}'>−</button>";
    } else {
        $icon = "<div class='locked-icon' title='Assigned to another CSR'>🔒</div>";
    }

    /* ========================================================
       LAST MESSAGE PREVIEW
    ========================================================= */
    $lastMsg = $row["last_msg"]
        ? htmlspecialchars($row["last_msg"])
        : "No messages yet";
?>
<div class="client-item"
     data-id="<?= $cid ?>"
     data-name="<?= $name ?>"
     data-status="<?= $statusRaw ?>">

    <div class="client-info">
        <strong><?= $name ?></strong>
        <div class="last-msg"><?= $lastMsg ?></div>
    </div>

    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
        <?= $badge ?>
        <?= $icon ?>
    </div>

</div>

<?php endforeach; ?>
