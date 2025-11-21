<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? "";
$search = $_GET["search"] ?? "";

$sql = "
SELECT c.id, c.name, c.assigned_csr,
(SELECT COUNT(*) FROM chat m 
 WHERE m.client_id = c.id 
 AND m.sender_type = 'client'
 AND m.created_at > COALESCE((SELECT last_read 
     FROM chat_read 
     WHERE client_id=c.id AND csr=:csr), '2000-01-01')
) AS unread
FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY c.name ASC";

$stmt = $conn->prepare($sql);
$params = [":csr"=>$csr];
if ($search !== "") $params[":search"] = "%$search%";
$stmt->execute($params);

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);
    $avatar = "upload/default-avatar.png";

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <img src='$avatar' class='client-avatar'>
        <div class='client-content'>
            <div class='client-name'>$name
            ".($unread > 0 ? "<span class='badge'>$unread</span>" : "")."</div>
            <div class='client-sub'>".(
                $assigned === null ? "Unassigned" :
                ($assigned === $csr ? "Assigned to YOU" :
                "Assigned to $assigned")
            )."</div>
        </div>
    </div>";
}
