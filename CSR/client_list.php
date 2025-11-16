<?php
include "../db_connect.php";
$rows = $conn->query("SELECT id,name,assigned_csr,last_active FROM clients ORDER BY last_active DESC")->fetchAll();
foreach ($rows as $r){
    echo "
    <div class='client-item' onclick='selectClient({$r['id']}, \"{$r['name']}\")'>
      <img src='lion.png' class='client-avatar'>
      <div>
        <b>{$r['name']}</b>
        <p style='font-size:12px;color:gray;'>Assigned: {$r['assigned_csr']}</p>
      </div>
    </div>";
}
?>
