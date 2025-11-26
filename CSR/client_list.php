<?php

include '../db_connect.php';

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];
$search = $_GET["search"] ?? "";

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.district,
        u.barangay,
        u.assigned_csr,
        u.is_online,
        (
            SELECT COUNT(*)
            FROM chat c
            WHERE c.client_id = u.id
              AND c.sender_type = 'client'
              AND c.seen = false
        ) AS unread
    FROM users u
";

if ($search !== "") {
    $sql .= " WHERE LOWER(u.full_name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, full_name ASC";

$stmt = $conn->prepare($sql);

if ($search !== "") $stmt->execute([":search" => "%$search%"]);
else $stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row["id"];
    $name = htmlspecialchars($row["full_name"]);
    $email = htmlspecialchars($row["email"]);
    $online = $row["is_online"] ? "online-dot" : "offline-dot";
    $unread = intval($row["unread"]);
    $assigned = $row["assigned_csr"];

    $badge = ($unread > 0) ? "<span class='badge'>$unread</span>" : "";
    $icon = "";

    if ($assigned === $csrUser) {
        $icon = "<i class='fa-solid fa-user-check assigned'></i>";
    } elseif (!empty($assigned)) {
        $icon = "<i class='fa-solid fa-lock locked'></i>";
    } else {
        $icon = "<i class='fa-solid fa-user-plus unassigned'></i>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <div class='avatar-small'>$icon</div>
        <div class='client-content'>
            <div class='client-name'>$name $badge</div>
            <div class='client-sub'>$email</div>
        </div>
        <span class='$online status-dot'></span>
    </div>
    ";
}
?>
