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
   Client list, unread count, typing indicator
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
            AND m.seen = false
            AND m.created_at > COALESCE(
                (SELECT MAX(last_read)
                 FROM chat_read r
                 WHERE r.client_id = c.id
                 AND r.csr = :csr),
                '2000-01-01'
            )
        ) AS unread,

        (
            SELECT client_typing
            FROM typing_status t
            WHERE t.client_id = c.id
            LIMIT 1
        ) AS typing

    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, c.name ASC";

$stmt = $conn->prepare($sql);

$params = [":csr" => $csrUser];
if ($search !== "") {
    $params[":search"] = "%$search%";
}

$stmt->execute($params);

$avatar = "upload/default-avatar.png";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = (int)$row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);
    $typing = $row["typing"];

    // Action button
    if ($assigned === null) {
        $btn = "<button class='assign-btn' onclick='event.stopPropagation(); showAssignPopup($id)'><i class=\"fa-solid fa-plus\"></i></button>";
    } elseif ($assigned === $csrUser) {
        $btn = "<button class='unassign-btn' onclick='event.stopPropagation(); showUnassignPopup($id)'><i class=\"fa-solid fa-minus\"></i></button>";
    } else {
        $btn = "<button class='lock-btn' disabled><i class=\"fa-solid fa-lock\"></i></button>";
    }

    // Subtitle
    $subtitle =
        ($assigned === null
            ? "Unassigned"
            : ($assigned === $csrUser
                ? "Assigned to YOU"
                : "Assigned to $assigned"));

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <img src='$avatar' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>
                $name

                " . ($typing ? "<span class='typing-badge'>Typing...</span>" : "") . "

                " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>

            <div class='client-sub'>$subtitle</div>
        </div>

        <div class='client-actions'>$btn</div>
    </div>
    ";
}
?>
