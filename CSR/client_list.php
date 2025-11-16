<?php
include "../db_connect.php";

$stmt = $conn->query("SELECT id,name,assigned_csr,last_active FROM clients ORDER BY last_active DESC");
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($list as $c) {
    $avatar = "lion.png";

    echo "
    <div class='client-item' onclick='selectClient({$c['id']}, \"{$c['name']}\")'>
        <div class='client-main'>
            <img src='$avatar' class='client-avatar'>
            <div>
                <div class='client-name'>{$c['name']}</div>
                <div class='client-sub'>CSR: {$c['assigned_csr']}</div>
            </div>
        </div>
    </div>
    ";
}
?>
