<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$filter  = $_POST["filter"] ?? "all";

if (!$csrUser) exit("Session expired.");

$query = "
    SELECT 
        u.id,
        u.full_name,
        u.ticket_status,
        u.assigned_csr,
        u.ticket_lock,

        -- last message text
        (
            SELECT message 
            FROM chat 
            WHERE client_id = u.id AND deleted = FALSE
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS last_msg,

        -- last message time
        (
            SELECT created_at 
            FROM chat 
            WHERE client_id = u.id AND deleted = FALSE
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS last_time

    FROM users u
    ORDER BY u.full_name ASC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "<p style='padding:15px;color:#888;'>No clients found.</p>";
    exit;
}

function matchFilter($ticketStatus, $filter)
{
    if ($filter === "all") return true;
    return strtolower($ticketStatus) === strtolower($filter);
}

foreach ($clients as $row):

    $cid           = $row["id"];
    $name          = htmlspecialchars($row["full_name"]);
    $status        = strtolower($row["ticket_status"] ?? "unresolved");
    $assignedTo    = $row["assigned_csr"];
    $isLocked      = intval($row["ticket_lock"]) === 1;

    if (!matchFilter($status, $filter)) continue;

    /* STATUS BADGES */
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

    /* ACTION ICON */
    if ($assignedTo === null) {

        // Unassigned → Show + button
        $icon = "<button class='assign-btn' data-id='$cid'>+</button>";

    } else if ($assignedTo === $csrUser) {

        // Assigned to ME → Show − button
        $icon = "<button class='unassign-btn' data-id='$cid'>−</button>";

    } else {

        // Assigned to another CSR → lock icon
        $icon = "<div class='locked-icon' data-id='$cid' title='Assigned to $assignedTo'>🔒</div>";
    }

    $lastMsg = $row["last_msg"] ? htmlspecialchars($row["last_msg"]) : "No messages yet";

?>
    <div class="client-item" data-id="<?= $cid ?>" data-name="<?= $name ?>">

        <div class="client-info">
            <strong><?= $name ?></strong>
            <div class="last-msg"><?= $lastMsg ?></div>
        </div>

        <div style="display:flex;flex-direction:column;align-items:end;gap:6px;">
            <?= $badge ?>
            <?= $icon ?>
        </div>

    </div>
<?php endforeach; ?>
