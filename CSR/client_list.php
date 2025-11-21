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
    SELECT id, name, assigned_csr
    FROM clients
";

if ($search !== "") {
    $sql .= " WHERE LOWER(name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY name ASC";

$stmt = $conn->prepare($sql);
$params = [];
if ($search !== "") $params[":search"] = "%$search%";
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $avatar   = "upload/default-avatar.png";

    // Determine UI button
    if ($assigned === null) {
        $button = "<button class='assign-btn' onclick='assignClient($id)'>＋</button>";
        $status = "Unassigned";
    } elseif ($assigned === $csrUser) {
        $button = "<button class='unassign-btn' onclick='unassignClient($id)'>－</button>";
        $status = "Assigned to YOU";
    } else {
        $button = "<button class='lock-btn' disabled>🔒</button>";
        $status = "Assigned to $assigned";
    }

    echo "
    <div class='client-item' id='client-$id'>
        <img src='$avatar' class='client-avatar'>
        <div class='client-content' onclick='selectClient($id, \"$name\", \"$assigned\")'>
            <div class='client-name'>$name</div>
            <div class='client-sub'>$status</div>
        </div>
        <div class='client-actions'>$button</div>
    </div>
    ";
}
?>
