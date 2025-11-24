<?php
session_start();
include "../db_connect.php";
header("Content-Type: text/html; charset=utf-8");

$csrUser = $_SESSION["csr_user"] ?? null;
if (!$csrUser) {
    http_response_code(401);
    exit("Unauthorized");
}

$search = $_GET["search"] ?? "";

// MAIN QUERY FOR CLIENT SIDEBAR
$sql = "
    SELECT
        c.id,
        c.name,
        c.assigned_csr,

        (
            SELECT COUNT(*) FROM chat m
            WHERE m.client_id = c.id
            AND m.sender_type = 'client'
            AND m.seen = false
        ) AS unread,

        (
            SELECT ts.csr_typing
            FROM typing_status ts
            WHERE ts.client_id = c.id
        ) AS typing
    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, c.name ASC";

$stmt = $conn->prepare($sql);
$params = [];
if ($search !== "") $params[":search"] = "%$search%";
$stmt->execute($params);

$avatar = "upload/default-avatar.png";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id   = $row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);
    $typing = intval($row["typing"]);

    $badge = ($unread > 0) ? "<span class='badge'>$unread</span>" : "";
    $typingLabel = ($typing == 1) ? "<span class='typing-dots'>typing...</span>" : "";

    echo "
    <div class='client-item'
         id='client-$id'
         data-id='$id'
         data-name='$name'
         data-assigned='$assigned'
         onclick='selectClient($id, \"$name\", \"$assigned\")'>

        <img src='$avatar' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>$name $badge</div>
            <div class='client-sub'>
                $typingLabel
            </div>
        </div>
    </div>";
}
?>
