<?php
if (!isset($_SESSION)) session_start();
require __DIR__ . "/../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$filter  = $_POST["filter"] ?? "all";

if (!$csrUser) exit("Session expired.");

$query = "
    SELECT 
        u.id,
        u.full_name,
        u.assigned_csr,
        u.ticket_lock,

        (SELECT message FROM chat WHERE client_id = u.id AND deleted = 0 ORDER BY created_at DESC LIMIT 1) AS last_msg,
        (SELECT created_at FROM chat WHERE client_id = u.id AND deleted = 0 ORDER BY created_at DESC LIMIT 1) AS last_time,

        (SELECT status FROM tickets WHERE client_id = u.id ORDER BY created_at DESC LIMIT 1) AS ticket_status
    FROM users u
    ORDER BY full_name ASC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

function matchFilter($status, $filter) {
    if ($filter === "all") return true;
    if (!$status) $status = "unresolved";
    return strtolower($status) === strtolower($filter);
}

foreach ($clients as $row):

    $cid    = $row["id"];
    $name   = htmlspecialchars($row["full_name"]);
    $status = strtolower($row["ticket_status"] ?? "unresolved");
    $assignedTo = $row["assigned_csr"];

    if (!matchFilter($status, $filter)) continue;

    switch ($status) {
        case "resolved":   $badge = "<span class='ticket-badge resolved'>RESOLVED</span>"; break;
        case "pending":    $badge = "<span class='ticket-badge pending'>PENDING</span>"; break;
        default:           $badge = "<span class='ticket-badge unresolved'>UNRESOLVED</span>";
    }

    if ($assignedTo === null)
        $icon = "<button class='assign-btn' data-id='$cid'>+</button>";
    else if ($assignedTo === $csrUser)
        $icon = "<button class='unassign-btn' data-id='$cid'>−</button>";
    else
        $icon = "<div class='locked-icon' title='Assigned to $assignedTo'>🔒</div>";

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
