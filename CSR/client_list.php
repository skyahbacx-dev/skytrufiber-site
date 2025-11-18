<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"];

// SEARCH filter
$search = $_GET["search"] ?? "";

$stmt = $conn->prepare("
    SELECT id, name, assigned_csr
    FROM clients
    WHERE name LIKE :s
    ORDER BY last_active DESC
");
$stmt->execute([":s" => "%$search%"]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];

    echo "<div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
            <div class='client-main'>
                <img src='lion.png' class='client-avatar'>
                <div>
                    <div class='client-name'>$name</div>
                    <div class='client-sub'>";

    if ($assigned === null || $assigned === "") {
        echo "Unassigned";
    } elseif ($assigned === $csrUser) {
        echo "Assigned to YOU";
    } else {
        echo "Assigned to $assigned";
    }

    echo "</div>
          </div>
        </div>
        <div class='client-actions'>";
    
    if ($assigned === null || $assigned === "") {
        echo "<button class='pill green' onclick='event.stopPropagation(); assignClient($id)'>➕</button>";
    } elseif ($assigned === $csrUser) {
        echo "<button class='pill red' onclick='event.stopPropagation(); unassignClient($id)'>➖</button>";
    } else {
        echo "<button class='pill gray' disabled title='Handled by $assigned'>🔒</button>";
    }

    echo "</div></div>";
}
?>
