<?php
include "../db_connect.php";

$stmt = $conn->query("SELECT id,name,assigned_csr,last_active FROM clients ORDER BY last_active DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as $c) {
    $status = (strtotime($c["last_active"]) > time()-60) ? "online" : "offline";

    echo "
    <div class='client-item' onclick='selectClient({$c["id"]}, \"{$c["name"]}\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div>
            <div class='client-name'>{$c["name"]}</div>
            <div class='client-sub'>
                <span class='{$status}-dot'></span> $status • CSR: {$c["assigned_csr"]}
            </div>
        </div>
    </div>
    ";
}
?>
