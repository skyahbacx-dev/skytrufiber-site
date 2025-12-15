<?php
/* ============================================================
   FORCE CSR SESSION — FIXES "Session expired" on refresh
============================================================ */
ini_set('session.name', 'CSRSESSID');
session_start();

/* Prevent caching */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

require __DIR__ . "/../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$filter  = $_POST["filter"] ?? "all"; // all | unresolved | pending | resolved

/* If CSR session is gone → return only this exact text */
if (!$csrUser) {
    echo "Session expired.";
    exit;
}

/* ============================================================
   FETCH CLIENT LIST — with latest ticket + latest message
============================================================ */
$stmt = $conn->prepare("
    SELECT
        u.id,
        u.full_name,
        u.assigned_csr,
        u.ticket_lock,

        /* Latest chat message */
        (
            SELECT message 
            FROM chat 
            WHERE client_id = u.id 
              AND deleted IS FALSE
            ORDER BY id DESC 
            LIMIT 1
        ) AS last_msg,

        /* Latest chat timestamp */
        (
            SELECT created_at 
            FROM chat 
            WHERE client_id = u.id 
              AND deleted IS FALSE
            ORDER BY id DESC 
            LIMIT 1
        ) AS last_time,

        /* Latest ticket status */
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
   RENDER CLIENT LIST
============================================================ */

foreach ($clients as $row):

    $cid         = $row["id"];
    $name        = htmlspecialchars($row["full_name"]);
    $statusRaw   = strtolower($row["ticket_status"] ?? "unresolved");

    /* Normalize default status */
    $status = $statusRaw ?: "unresolved";

    $assignedTo  = $row["assigned_csr"];
    $isLocked    = ($row["ticket_lock"] ? true : false);

    /* Skip if filter mismatch */
    if (!matchFilter($status, $filter)) continue;

    /* Badge style */
    switch ($status) {
        case "resolved":
            $badge = "<span class='ticket-badge resolved'>RESOLVED</span>";
            break;
        case "pending":
            $badge = "<span class='ticket-badge pending'>PENDING</span>";
            break;
        default:
            $badge = "<span class='ticket-badge unresolved'>UNRESOLVED</span>";
            break;
    }

    /* Right-side icons: assign / unassign / locked */
    if (!$assignedTo) {
        $icon = "<button class='assign-btn' data-id='$cid'>+</button>";
    }
    elseif ($assignedTo === $csrUser) {
        $icon = "<button class='unassign-btn' data-id='$cid'>−</button>";
    }
    else {
        $icon = "<div class='locked-icon' data-id='$cid'>🔒</div>";
    }

    /* Preview of last chat */
    $lastMsg = $row["last_msg"]
        ? htmlspecialchars($row["last_msg"])
        : "No messages yet";
?>

<div class="client-item" data-id="<?= $cid ?>" data-name="<?= $name ?>">
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
