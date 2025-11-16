<?php
include "../db_connect.php";

$stmt = $conn->query("SELECT id,name,assigned_csr,last_active FROM clients ORDER BY last_active DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as $row) {
    echo "
    <div class='client-item' onclick='selectClient({$row['id']}, \"{$row['name']}\")'>
        <img src=\"lion.png\" class='client-avatar'>
        <div class='client-info'>
            <b>{$row['name']}</b>
            <p>Assigned to {$row['assigned_csr']}</p>
        </div>
    </div>
    ";
}
?>
