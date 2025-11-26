<?php
session_start();
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
        (
            SELECT COUNT(*)
            FROM chat c
            WHERE c.user_id = u.id
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

$params = [];
if ($search !== "") $params[":search"] = "%$search%";

$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id = $row["id"];
    $name = htmlspecialchars($row["full_name"]);
    $unread = intval($row["unread"]);

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <div class='client-icon'>
            <img src=\"upload/default-avatar.png\" class='client-avatar'>
            <span class='status-dot offline'></span>
        </div>

        <div class='client-info'>
            <div class='client-name'>$name</div>
            <div class='client-email'>{$row["email"]}</div>
        </div>

        " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
    </div>
    ";
}
?>
