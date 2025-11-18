<?php
session_start();
require "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? "";

// SEARCH
$search = $_GET["search"] ?? "";
$searchQuery = $search ? "WHERE name ILIKE '%$search%'" : "";

// FETCH CLIENT LIST
$stmt = $conn->query("
    SELECT id, name, assigned_csr
    FROM clients
    $searchQuery
    ORDER BY name ASC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id   = $row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];

    echo "<div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
            <div class='client-main'>
                <img src='upload/default-avatar.png' class='client-avatar'>
                <div>
                    <div class='client-name'>$name</div>
                    <div class='client-sub'>" .
                        ($assigned ? "Assigned to $assigned" : "Unassigned")
                    . "</div>
                </div>
            </div>";

    echo "<div class='client-actions'>";

    // ==== BUTTON LOGIC ====
    if (!$assigned) {
        echo "<button class='pill pill-add' onclick='event.stopPropagation(); assignClient($id)'>Add</button>";
    }
    elseif ($assigned === $csrUser) {
        echo "<button class='pill pill-remove' onclick='event.stopPropagation(); unassignClient($id)'>Remove</button>";
    }
    else {
        echo "<button class='pill pill-locked' disabled>Locked</button>";
    }

    echo "</div></div>";
}
?>
