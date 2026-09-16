<?php
if (!isset($_SESSION)) session_start();
require __DIR__ . "/../../db_connect.php";

$ticket = intval($_GET["ticket"] ?? 0);

$stmt = $conn->prepare("
SELECT t.id, t.status, u.full_name, u.account_number 
FROM tickets t
JOIN users u ON u.id = t.client_id
WHERE t.id = ?
");
$stmt->execute([$ticket]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

$msg = $conn->prepare("SELECT sender_type, message, deleted, created_at FROM chat WHERE ticket_id = ? ORDER BY created_at ASC");
$msg->execute([$ticket]);
?>
<!DOCTYPE html>
<html>
<head>
<title>Chat Export</title>
<style>
body { font-family: Arial; padding: 20px; background:#f7f7f7; }
h2 { text-align:center; }
.msg { max-width:60%; padding:10px 15px; border-radius:12px; margin:10px 0; }
.csr { background:#e1ffd8; margin-left:auto; }
.client { background:#d8e8ff; margin-right:auto; }
.time { font-size:11px; color:#666; margin-top:3px; }
</style>
</head>
<body>

<h2>Chat Transcript — Ticket #<?= $ticket ?></h2>
<p><strong>Client:</strong> <?= $info["full_name"] ?> (<?= $info["account_number"] ?>)</p>

<hr>

<?php foreach ($msg as $m): ?>
<div class="msg <?= $m["sender_type"] === 'csr' ? 'csr' : 'client' ?>">
    <?= $m["deleted"] ? "<i>Deleted message</i>" : nl2br(htmlspecialchars($m["message"])) ?>
    <div class="time"><?= date("M j, Y g:i A", strtotime($m["created_at"])) ?></div>
</div>
<?php endforeach; ?>

</body>
</html>
