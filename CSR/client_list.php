<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;

if (!$csrUser) {
    http_response_code(401);
    exit("Unauthorized");
}

$search = $_GET["search"] ?? "";

$sql = "
    SELECT id, name, assigned_csr, last_active
    FROM clients
";

if ($search !== "") {
    $sql .= " WHERE LOWER(name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY last_active DESC";

$stmt = $conn->prepare($sql);

if ($search !== "") {
    $stmt->execute([":search" => "%$search%"]);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $isMine   = ($assigned === $csrUser);

    // Static default avatar
    $avatar = "upload/default-avatar.png";

    echo "<div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
            <div class='client-main'>
                <img src=\"$avatar\" class='client-avatar'>
                <div>
                    <div class='client-name'>$name</div>
                    <div class='client-sub'>";

    if ($assigned === null || $assigned === "") {
        echo "Unassigned";
    } elseif ($isMine) {
        echo "Assigned to YOU";
    } else {
        echo "Assigned to $assigned";
    }

    echo "</div></div></div>
            <div class='client-actions'>";

    if ($assigned === null || $assigned === "") {
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
