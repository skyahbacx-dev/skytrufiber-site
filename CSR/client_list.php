<?php
session_start();
require "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$search  = $_GET["search"] ?? "";

// If session missing, prevent null bug
if (!$csrUser) {
    exit("SESSION ERROR");
}

$query = "
    SELECT id, name, assigned_csr
    FROM clients
    WHERE name ILIKE :search
    ORDER BY name ASC
";

$stmt = $conn->prepare($query);
$stmt->execute([":search" => "%$search%"]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id   = $row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    
    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <div class='client-main'>
            <img src='upload/default-avatar.png' class='client-avatar'>
            <div>
                <div class='client-name'>$name</div>
                <div class='client-sub'>" . ($assigned ? "Assigned to $assigned" : "Unassigned") . "</div>
            </div>
        </div>
        <div class='client-actions'>
    ";

    if ($assigned === null || $assigned === "") {
        echo "<button class='pill green' onclick='event.stopPropagation(); assignClient($id)'>➕</button>";
    }
    elseif ($assigned === $csrUser) {
        echo "<button class='pill red' onclick='event.stopPropagation(); unassignClient($id)'>➖</button>";
    }
    else {
        echo "<button class='pill gray' disabled>🔒</button>";
    }

    echo "</div></div>";
}
?>
