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

/*
   FIXED unread message counter:
   Use MAX(last_read) to ensure subquery returns only 1 row.
*/
$sql = "
    SELECT
        c.id,
        c.name,
        c.assigned_csr,

        (
            SELECT COUNT(*) FROM chat m
            WHERE m.client_id = c.id
            AND m.sender_type = 'client'
            AND m.seen = 0
            AND m.created_at > COALESCE(
                (SELECT MAX(last_read)
                 FROM chat_read r
                 WHERE r.client_id = c.id
                 AND r.csr = :csr),
                '2000-01-01'
            )
        ) AS unread

    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, c.name ASC";

$stmt = $conn->prepare($sql);

$params = [":csr" => $csrUser];
if ($search !== "") $params[":search"] = "%$search%";

$stmt->execute($params);

$avatar = "upload/default-avatar.png";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id   = $row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <img src='$avatar' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>

            <div class='client-sub'>
                " . ($assigned === null ? "Unassigned"
                    : ($assigned === $csrUser ? "Assigned to YOU"
                    : "Assigned to $assigned")) . "
            </div>
        </div>
    </div>";
}
?>
