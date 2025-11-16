<?php
include "../db_connect.php";
session_start();

$csr = $_SESSION["csr_user"];

$query = $conn->query("
    SELECT id, name, assigned_csr, last_active
    FROM clients
    ORDER BY last_active DESC
");

while ($r = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '
    <div class="client-item" onclick="openChat('.$r['id'].', \''.$r['name'].'\')">
        <img src="lion.png" class="client-avatar">
        <div class="client-info">
            <b>'.$r["name"].'</b>
            <p>Assigned to '.$r["assigned_csr"].'</p>
        </div>
    </div>
    ';
}
?>
