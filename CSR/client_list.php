<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
if (!$csrUser) {
    http_response_code(401);
    exit("Unauthorized");
}

$search = $_GET["search"] ?? "";

/*
   Pull clients with unread count and assignment status
*/
$sql = "
    SELECT c.id, c.name, c.assigned_csr,
    (
        SELECT COUNT(*) FROM chat m
        WHERE m.client_id = c.id
        AND m.sender_type = 'client'
        AND m.seen = FALSE
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

    $id         = $row["id"];
    $name       = htmlspecialchars($row["name"]);
    $assigned   = $row["assigned_csr"];
    $unread     = intval($row["unread"]);
    $avatar     = "upload/default-avatar.png";

    $isMine     = ($assigned === $csrUser);
    $isFree     = (!$assigned || $assigned === "");
    $isLocked   = (!$isFree && !$isMine);

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>

        <img src='$avatar' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>

            <div class='client-sub'>" .
                ($isFree ? "Unassigned"
                : ($isMine ? "Assigned to YOU"
                : "Assigned to $assigned")) . "
            </div>
        </div>

        <div class='client-actions'>
    ";

    if ($isFree) {
        echo "<button class='pill green' onclick='event.stopPropagation(); showAssignPopup($id)'>➕</button>";
    }
    elseif ($isMine) {
        echo "<button class='pill red' onclick='event.stopPropagation(); showUnassignPopup($id)'>➖</button>";
    }
    else {
        echo "<button class='pill gray' disabled>🔒</button>";
    }

    echo "
        </div>
    </div>
    ";
}
?>
