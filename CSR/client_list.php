<?php
include '../db_connect.php';

$clients = $conn->query("SELECT id,name,email,assigned_csr,last_active FROM clients ORDER BY name ASC");

while ($c = $clients->fetch(PDO::FETCH_ASSOC)) {

    $status = (strtotime($c["last_active"]) > time() - 60) ? "Online" : "Offline";
    $avatar = ($c["name"][0] <= "M") ? "lion.PNG" : "penguin.PNG";

    echo "
    <div class='client-item' onclick=\"openClient({$c['id']},'{$c['name']}','{$avatar}')\">
        <div class='client-main'>
            <img class='client-avatar' src='{$avatar}'>
            <div class='client-meta'>
                <div class='client-name'>{$c['name']}</div>
                <div class='client-sub'>
                    <span class='".($status=='Online'?'online-dot':'offline-dot')."'></span>
                    {$status} • ".($c['assigned_csr'] ?: 'Unassigned')."
                </div>
            </div>
        </div>
    </div>
    ";
}
?>
