<?php
require "../dbconnect.php";

$search = $_GET["search"] ?? "";
$csrUser = $_SESSION["csr_user"];

// BASE QUERY
$sql = "
SELECT 
    c.id,
    c.fullname,
    c.assigned_to,
    c.avatar,
    (
        SELECT COUNT(*) 
        FROM chat m 
        WHERE m.client_id = c.id 
        AND m.sender_type = 'client'
        AND m.seen = false
    ) AS unread
FROM clients c
WHERE c.is_deleted = false
";

// SEARCH FILTER
if ($search !== "") {
    $sql .= " AND (c.fullname ILIKE :search)";
}

$sql .= " ORDER BY unread DESC, c.fullname ASC";

$stmt = $pdo->prepare($sql);

if ($search !== "") {
    $stmt->bindValue(":search", "%$search%");
}

$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php foreach ($clients as $c): 
    $unread = (int)$c["unread"];
    $assigned = $c["assigned_to"];
    $activeClass = "";
?>

<div class="client-item" id="client-<?= $c['id'] ?>" 
     onclick="selectClient(<?= $c['id'] ?>, '<?= htmlspecialchars($c['fullname']) ?>', '<?= $assigned ?>')">

    <img src="<?= $c['avatar'] ?: 'upload/default-avatar.png' ?>" class="client-avatar">

    <div class="client-info">
        <div class="client-name"><?= htmlspecialchars($c['fullname']) ?></div>
        <small class="assigned-text">
            <?= $assigned ? "Assigned to " . htmlspecialchars($assigned) : "Unassigned" ?>
        </small>
    </div>

    <!-- unread bubble -->
    <?php if ($unread > 0): ?>
        <span class="badge-unread"><?= $unread ?></span>
    <?php endif; ?>

    <!-- assign icons -->
    <div class="assign-btn">
        <?php if (!$assigned): ?>
            <button onclick="event.stopPropagation(); showAssignPopup(<?= $c['id'] ?>)">➕</button>
        <?php elseif ($assigned === $csrUser): ?>
            <button onclick="event.stopPropagation(); showUnassignPopup(<?= $c['id'] ?>)">➖</button>
        <?php else: ?>
            <button class="lock" disabled>🔒</button>
        <?php endif; ?>
    </div>

</div>

<?php endforeach; ?>
