<?php
include "../db_connect.php";

$csr_user = $_SESSION["csr_user"] ?? "";

$stmt = $conn->prepare("
    SELECT id, name, assigned_csr, last_active
    FROM clients
    ORDER BY name ASC
");
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $status = "Offline";
    if ($row["last_active"] && strtotime($row["last_active"]) > time() - 60) {
        $status = "Online";
    }

    $avatar = (strtoupper(substr($row["name"], 0, 1)) <= "M")
                ? "CSR/lion.PNG" : "CSR/penguin.PNG";

    echo "
    <div class='client-item' onclick=\"openClient({$row['id']}, '{$row['name']}')\">
        <div class='client-main'>
            <img src='$avatar' class='client-avatar'>
            <div class='client-meta'>
                <div class='client-name'>{$row['name']}</div>
                <div class='client-sub'>
                    <span class='".($status=="Online" ? "online-dot" : "offline-dot")."'></span>
                    $status • ".($row["assigned_csr"] ?: "Unassigned")."
                </div>
            </div>
        </div>
    </div>";
}
?>
