<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;

// Fetch all clients
$query = $conn->query("
    SELECT id, full_name, email, assigned_csr, locked
    FROM users
    ORDER BY full_name ASC
");
$clients = $query->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "<p style='padding:13px; color:#777'>No clients found.</p>";
    exit;
}

foreach ($clients as $c):
    $id    = $c['id'];
    $name  = htmlspecialchars($c['full_name']);
    $email = htmlspecialchars($c['email']);
    $assigned = $c['assigned_csr'];
    $locked = $c['locked'] == 1;

// Determine icon type
    $icon = "";
    $iconAction = "";
    
    if ($locked) {
        $icon = "<i class='fa fa-lock'></i>";
        $iconAction = "lockClient($id)";
    } 
    else if ($assigned === $csrUser) {
        $icon = "<i class='fa fa-minus'></i>";
        $iconAction = "removeClient($id)";
    } 
    else if ($assigned === null || $assigned === "") {
        $icon = "<i class='fa fa-plus'></i>";
        $iconAction = "assignClient($id)";
    } 
    else {
        $icon = "<i class='fa fa-lock'></i>";
        $iconAction = "lockClient($id)";
    }
?>

<div class="client-item" data-id="<?= $id ?>" data-name="<?= $name ?>">
    <div class="client-row-left">
        <strong><?= $name ?></strong><br>
        <small><?= $email ?></small>
    </div>

    <button class="client-action-btn" onclick="<?= $iconAction ?>; event.stopPropagation();">
        <?= $icon ?>
    </button>
</div>

<?php endforeach; ?>
