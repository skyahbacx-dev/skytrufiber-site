<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? "";
$search = $_GET["search"] ?? "";

$query = "
    SELECT id, name, assigned_csr, last_active, avatar
    FROM clients
";

if ($search) {
    $query .= " WHERE name ILIKE :search ";
}

$query .= " ORDER BY last_active DESC";

$stmt = $conn->prepare($query);
$params = $search ? [":search" => "%$search%"] : [];
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];

    // avatar fallback
    $avatar = $row["avatar"];
    if (!$avatar || $avatar === "") {
        $avatar = "upload/default-avatar.png";   // DEFAULT FACEBOOK-LIKE CIRCLE AVATAR
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <div class='client-main'>
            <img src='$avatar' class='client-avatar'>
            <div>
                <div class='client-name'>$name</div>
                <div class='client-sub'>"
                . ($assigned ? "Assigned to $assigned" : "Unassigned") .
                "</div>
            </div>
        </div>
        <div class='client-actions'>
    ";

    if ($assigned === null || $assigned === "") {
        echo "<button class='pill green' onclick='event.stopPropagation(); assignClient($id)'>➕</button>";
    } elseif ($assigned === $csrUser) {
        echo "<button class='pill red' onclick='event.stopPropagation(); unassignClient($id)'>➖</button>";
    } else {
        echo "<button class='pill gray' disabled title='Handled by $assigned'>🔒</button>";
    }

    echo "
        </div>
    </div>";
}
?>
