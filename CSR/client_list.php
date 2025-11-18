<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? ""; // username like CSR1
$search  = trim($_GET["search"] ?? "");

$sql = "SELECT id, name, assigned_csr, last_active 
        FROM clients 
        WHERE name ILIKE :search 
        ORDER BY last_active DESC NULLS LAST, id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([":search" => "%$search%"]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p style='padding:8px;color:#777;'>No clients found.</p>";
    exit;
}

foreach ($rows as $row) {
    $id        = (int)$row["id"];
    $name      = htmlspecialchars($row["name"] ?? "Unknown");
    $assigned  = $row["assigned_csr"];        // username of CSR or null
    $safeName  = htmlspecialchars($name, ENT_QUOTES);
    $safeAssign = htmlspecialchars($assigned ?? "", ENT_QUOTES);

    echo "<div class='client-item' id='client-$id'
              onclick=\"selectClient($id, '$safeName', '$safeAssign')\">
            <div class='client-main'>
                <img src='upload/default-avatar.png' class='client-avatar'>
                <div>
                    <div class='client-name'>$name</div>
                    <div class='client-sub'>";

    if ($assigned === null || $assigned === "") {
        echo "Unassigned";
    } elseif ($assigned === $csrUser) {
        echo "Assigned to YOU ($assigned)";
    } else {
        echo "Assigned to $assigned";
    }

    echo    "</div>
            </div>
        </div>
        <div class='client-actions'>";

    if ($assigned === null || $assigned === "") {
        echo "<button class='assign-btn' onclick='event.stopPropagation(); assignClient($id)'>＋</button>";
    } elseif ($assigned === $csrUser) {
        echo "<button class='unassign-btn' onclick='event.stopPropagation(); unassignClient($id)'>－</button>";
    } else {
        echo "<button class='lock-btn' disabled title='Handled by $assigned'>🔒</button>";
    }

    echo "</div></div>";
}
?>
