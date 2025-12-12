<?php
if (!isset($_SESSION)) session_start();
require __DIR__ . "/../../db_connect.php";

$ticket = intval($_GET["ticket"] ?? 0);

$stmt = $conn->prepare("
SELECT t.id, t.status, t.created_at, u.full_name, u.account_number 
FROM tickets t
JOIN users u ON u.id = t.client_id
WHERE t.id = ?
");
$stmt->execute([$ticket]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) exit("Invalid ticket");

$logs = $conn->prepare("SELECT action, csr_user, timestamp FROM ticket_logs WHERE client_id = (SELECT client_id FROM tickets WHERE id = ?) ORDER BY timestamp ASC");
$logs->execute([$ticket]);

$messages = $conn->prepare("SELECT sender_type, message, deleted, edited, created_at FROM chat WHERE ticket_id = ? ORDER BY created_at ASC");
$messages->execute([$ticket]);
?>
<!DOCTYPE html>
<html>
<head>
<title>Ticket Report</title>
<style>
body { font-family: Arial; padding: 25px; }
h1 { text-align:center; }
.table { width:100%; border-collapse: collapse; margin-top:20px; }
.table th, .table td { padding:10px; border:1px solid #ccc; }
.section { font-size:18px; margin-top:30px; font-weight:bold; }
.logo { width:120px; display:block; margin:auto; }
</style>
</head>
<body>

<img src="/AHBALOGO.png" class="logo">

<h1>Ticket Report #<?= $ticket ?></h1>

<h3>Client: <?= htmlspecialchars($info["full_name"]) ?> (<?= $info["account_number"] ?>)</h3>
<p>Status: <strong><?= strtoupper($info["status"]) ?></strong></p>
<p>Created: <?= date("M j, Y g:i A", strtotime($info["created_at"])) ?></p>

<div class="section">Timeline</div>
<table class="table">
<tr><th>Action</th><th>CSR</th><th>Date</th></tr>
<?php foreach ($logs as $l): ?>
<tr>
    <td><?= strtoupper($l["action"]) ?></td>
    <td><?= htmlspecialchars($l["csr_user"]) ?></td>
    <td><?= date("M j, Y g:i A", strtotime($l["timestamp"])) ?></td>
</tr>
<?php endforeach; ?>
</table>

<div class="section">Chat Messages</div>
<table class="table">
<tr><th>Sender</th><th>Message</th><th>Date</th></tr>
<?php foreach ($messages as $m): ?>
<tr>
    <td><?= $m["sender_type"] ?></td>
    <td><?= $m["deleted"] ? "<i>Deleted</i>" : nl2br(htmlspecialchars($m["message"])) ?></td>
    <td><?= date("M j, Y g:i A", strtotime($m["created_at"])) ?></td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
