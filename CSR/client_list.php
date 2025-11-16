<?php
include "../db_connect.php";

$stmt = $conn->query("SELECT id, name, assigned_csr, last_active FROM clients ORDER BY last_active DESC");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($clients as $c) {
    echo "
    <div class='client-row' onclick='selectClient({$c['id']}, `{$c['name']}`)'>
        <img src='lion.png' class='client-avatar'>
        <div class='client-info'>
            <b>{$c['name']}</b>
            <span>Assigned to {$c['assigned_csr']}</span>
        </div>
    </div>
    ";
}
?>
