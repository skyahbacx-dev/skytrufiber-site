<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? "";  // FIXED

$stmt = $conn->query("
    SELECT id, name, assigned_csr, last_active
    FROM clients
    ORDER BY last_active DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id        = $row["id"];
    $name      = htmlspecialchars($row["name"]);
    $assigned  = $row["assigned_csr"];
    $isMine    = ($assigned === $csrUser);
    $isFree    = ($assigned === null || $assigned === "");

    echo "<div class='client-item' onclick='selectClient($id, \"$name\", \"$assigned\")'>
            <div class='client-main'>
                <img src='upload/default-avatar.png' class='client-avatar'>
                <div>
                    <div class='client-name'>$name</div>
                    <div class='client-sub'>";

    if ($isFree) {
        echo "Unassigned";
    } elseif ($isMine) {
        echo "Assigned to YOU";
    } else {
        echo "Assigned to $assigned";
    }

    echo "</div></div></div>

          <div class='client-actions'>";
    
    if ($isFree) {
        echo "<button class='pill green' onclick='event.stopPropagation(); assignClient($id)'>➕</button>";
    }
    elseif ($isMine) {
        echo "<button class='pill red' onclick='event.stopPropagation(); unassignClient($id)'>➖</button>";
    }
    else {
        echo "<button class='pill gray' disabled title='Handled by $assigned'>🔒</button>";
    }

    echo "</div></div>";
}
?>
