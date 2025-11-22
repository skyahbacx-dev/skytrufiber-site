<?php
session_start();
include "../db_connect.php";

$search = $_GET["search"] ?? "";
$csr = $_SESSION["csr_user"] ?? "";

$query = "
    SELECT c.id, c.name, c.assigned_csr,
        (SELECT COUNT(*) FROM chat WHERE client_id = c.id AND sender_type = 'client' AND seen = 0) AS unread
    FROM clients c
    WHERE c.name ILIKE :s
    ORDER BY c.assigned_csr = :csr DESC, unread DESC, c.name ASC
";

$stmt = $conn->prepare($query);
$stmt->execute([
    ":s" => "%$search%",
    ":csr" => $csr
]);

$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($clients as $row):
    $id = $row["id"];
    $name = $row["name"];
    $assigned = $row["assigned_csr"];
    $unread = $row["unread"];

    // Determine badge colors
    $isYours = ($assigned === $csr);
    $lockedByOther = ($assigned && !$isYours);
?>
    <div class="client-item" id="client-<?= $id ?>"
         onclick="selectClient(<?= $id ?>, '<?= htmlspecialchars($name) ?>', '<?= $assigned ?>')">

        <div class="client-avatar"></div>

        <div class="client-info">
            <div class="client-name"><?= htmlspecialchars($name) ?></div>

            <?php if ($isYours): ?>
                <span class="assign-status yours">Assigned to YOU</span>
            <?php elseif ($lockedByOther): ?>
                <span class="assign-status other">Assigned to <?= $assigned ?></span>
            <?php else: ?>
                <span class="assign-status none">Not Assigned</span>
            <?php endif; ?>
        </div>

        <div class="client-controls">
            <?php if ($isYours): ?>
                <button class="ctrl-btn unassign" onclick="event.stopPropagation(); showUnassignPopup(<?= $id ?>)">−</button>
            <?php elseif ($lockedByOther): ?>
                <button class="ctrl-btn lock" onclick="event.stopPropagation();" disabled>
                    <i class="fa-solid fa-lock"></i>
                </button>
            <?php else: ?>
                <button class="ctrl-btn assign" onclick="event.stopPropagation(); showAssignPopup(<?= $id ?>)">+</button>
            <?php endif; ?>

            <?php if ($unread > 0): ?>
                <span class="unread-badge"><?= $unread ?></span>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
