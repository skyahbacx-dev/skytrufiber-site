<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? "";
$search = $_GET["search"] ?? "";

$sql = "
    SELECT c.id, c.name, c.assigned_csr,
    (
        SELECT COUNT(*)
        FROM chat
        WHERE client_id = c.id
        AND sender_type = 'client'
        AND id NOT IN (
            SELECT message_id FROM chat_read WHERE csr = :csrUser
        )
    ) AS unread
    FROM clients c
    WHERE c.name ILIKE :search
    ORDER BY c.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':search' => "%$search%",
    ':csrUser' => $csr
]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php foreach($clients as $c): ?>
<div class="client-item" id="client-<?= $c['id']; ?>"
     onclick="selectClient(<?= $c['id']; ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES); ?>', '<?= $c['assigned_csr']; ?>')">

    <img src="upload/default-avatar.png" class="client-avatar">

    <div class="client-info">
        <strong><?= htmlspecialchars($c["name"]); ?></strong>
        <small>
            <?php if ($c["assigned_csr"] == $csr): ?>
                Assigned to YOU
            <?php elseif ($c["assigned_csr"]): ?>
                Assigned to <?= $c["assigned_csr"]; ?>
            <?php else: ?>
                Unassigned
            <?php endif; ?>
        </small>
    </div>

    <?php if ($c["unread"] > 0): ?>
        <span class="badge"><?= $c["unread"]; ?></span>
    <?php endif; ?>
</div>
<?php endforeach; ?>
