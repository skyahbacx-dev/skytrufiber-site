<?php
include "../db_connect.php";
session_start();

$csr = $_SESSION["csr_fullname"] ?? $_SESSION["csr_user"];

$stmt = $conn->query("
    SELECT id, name, assigned_csr, last_active
    FROM clients
    ORDER BY last_active DESC NULLS LAST
");

while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // Determine status icon
    $statusIcon = "<span class='status-dot offline'></span>";

    // Determine control button
    if ($c["assigned_csr"] === NULL) {
        // UNASSIGNED
        $control = "<button class='assign-btn' onclick=\"assignClient({$c['id']})\">➕</button>";
    }
    elseif ($c["assigned_csr"] === $csr) {
        // ASSIGNED TO ME
        $control = "<button class='remove-btn' onclick=\"removeClient({$c['id']})\">➖</button>";
    }
    else {
        // ASSIGNED TO ANOTHER CSR
        $control = "<button class='lock-btn' disabled>🔒</button>";
    }

    echo "
    <div class='client-item' onclick=\"selectClient({$c['id']}, '{$c['name']}')\">
        <div class='c-name'>{$c['name']}</div>
        <div class='c-controls'>$control</div>
    </div>";
}
?>
