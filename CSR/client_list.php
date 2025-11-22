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
    SELECT c.id, c.name, c.assigned_csr,
    (
        SELECT COUNT(*) 
        FROM chat m
        WHERE m.client_id = c.id
        AND m.sender_type = 'client'
        AND m.created_at > (
            SELECT COALESCE(MAX(created_at), '2000-01-01')
            FROM chat
            WHERE client_id = c.id AND sender_type = 'csr'
        )
    ) AS unread
    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY c.name ASC";

$stmt = $conn->prepare($sql);
$params = [];
if ($search !== "") $params[":search"] = "%$search%";
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id = $row["id"];
    $name = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);
    $avatar = "upload/default-avatar.png";

    $isMine = ($assigned === $csrUser);
    $isUnassigned = ($assigned === null || $assigned === "");

    // Button UI rules
    if ($isUnassigned) {
        $actionButton = "<button class='assign-btn add' onclick='event.stopPropagation(); showAssignPopup($id)'>➕</button>";
    }
    elseif ($isMine) {
        $actionButton = "<button class='assign-btn remove' onclick='event.stopPropagation(); showUnassignPopup($id)'>➖</button>";
    }
    else {
        $actionButton = "<button class='assign-btn lock' disabled title='Assigned to $assigned'>🔒</button>";
    }

    $badge = ($unread > 0) ? "<span class='badge'>$unread</span>" : "";

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <img src='$avatar' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>$name $badge</div>
            <div class='client-sub'>" .
                ($isUnassigned
                    ? "Unassigned"
                    : ($isMine ? "Assigned to YOU" : "Assigned to $assigned"))
            . "</div>
        </div>

        <div class='client-action'>
            $actionButton
        </div>
    </div>
    ";
}
?>
