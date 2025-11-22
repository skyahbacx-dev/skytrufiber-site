<?php
session_start();
include "../db_connect.php";

header("Content-Type: text/html");

$csrUser = $_SESSION["csr_user"] ?? null;
if (!$csrUser) {
    http_response_code(401);
    exit("Unauthorized");
}

$search = $_GET["search"] ?? "";

/*
   Count unread messages:
   - Only messages sent by CLIENT
   - Count messages with ID greater than last seen chat_id by CSR
*/
$sql = "
    SELECT c.id, c.name, c.assigned_csr,
        (
            SELECT COUNT(*) FROM chat m
            WHERE m.client_id = c.id
              AND m.sender_type = 'client'
              AND m.id > COALESCE((
                    SELECT r.chat_id FROM chat_read r
                    WHERE r.client_id = c.id AND r.csr = :csr
                    ORDER BY r.chat_id DESC LIMIT 1
              ), 0)
        ) AS unread
    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY c.name ASC";

$stmt = $conn->prepare($sql);

$params = [":csr" => $csrUser];
if ($search !== "") $params[":search"] = "%$search%";

$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);
    $avatar   = "upload/default-avatar.png";

    $badge = ($unread > 0) ? "<span class='unread-badge'>$unread</span>" : "";

    $assignInfo = ($assigned === null)
        ? "Unassigned"
        : ($assigned === $csrUser ? "Assigned to YOU" : "Assigned to $assigned");

    if ($assigned === null) {
        $btn = "<button class='assign-btn' onclick='showAssignPopup($id); event.stopPropagation();'>+</button>";
    }
    elseif ($assigned === $csrUser) {
        $btn = "<button class='unassign-btn' onclick='showUnassignPopup($id); event.stopPropagation();'>−</button>";
    }
    else {
        $btn = "<button class='lock-btn' disabled>🔒</button>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <img src='$avatar' class='client-avatar'>
        
        <div class='client-content'>
            <div class='client-name'>$name $badge</div>
            <div class='client-sub'>$assignInfo</div>
        </div>

        <div class='client-action'>$btn</div>
    </div>";
}
?>
