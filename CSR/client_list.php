<?php
session_start();
require '../db_connect.php';

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
        u.profile_pic,
        u.is_online,
        (
            SELECT COUNT(*) FROM chat c
            WHERE c.user_id = u.id
            AND c.sender_type = 'client'
            AND c.seen = false
        ) AS unread
    FROM users u
";

if ($search !== "") {
    $sql .= " WHERE LOWER(u.full_name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, u.full_name ASC";

$stmt = $conn->prepare($sql);

if ($search !== "") {
    $stmt->execute([":search" => "%$search%"]);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id     = $row["id"];
    $name   = htmlspecialchars($row["full_name"]);
    $email  = htmlspecialchars($row["email"]);
    $avatar = $row["profile_pic"] ?: "upload/default-avatar.png";
    $online = $row["is_online"] ? "online" : "offline";
    $unread = intval($row["unread"]);

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <div class='client-icon'>
            <img src='$avatar' class='client-avatar'>
            <span class='status-dot $online'></span>
        </div>

        <div class='client-info'>
            <div class='client-name'>$name</div>
            <div class='client-email'>$email</div>
        </div>

        " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
    </div>
    ";
}
?>
