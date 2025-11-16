<?php
include "../db_connect.php";

$stmt = $conn->query("
    SELECT id, name, assigned_csr FROM clients
    ORDER BY id DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "
    <div class='client-item' data-id='{$row["id"]}' data-name='{$row["name"]}'>
        <div class='client-main'>
            <img src='../CSR/upload/penguin.PNG' class='client-avatar'>
            <div>
                <div class='client-name'>{$row["name"]}</div>
                <div class='client-sub'>Assigned to {$row["assigned_csr"]}</div>
            </div>
        </div>
    </div>
    ";
}
