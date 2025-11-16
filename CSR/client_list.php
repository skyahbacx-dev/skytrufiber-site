<?php
include "../db_connect.php";

$stmt = $conn->query("SELECT id, name, assigned_csr, last_active FROM clients ORDER BY name ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $status = (strtotime($row["last_active"]) > time() - 60) ? "Online" : "Offline";

    echo '
    <div class="client-item" onclick="openClient('.$row["id"].', \''.$row["name"].'\')">
        <img src="lion.PNG" class="client-avatar">
        <div>
            <div class="client-name">'.$row["name"].'</div>
            <div class="client-sub">
                <span class="'.($status=="Online"?"online-dot":"offline-dot").'"></span> '.$status.'
                • CSR: '.($row["assigned_csr"] ?: "Unassigned").'
            </div>
        </div>
    </div>
    ';
}
?>
