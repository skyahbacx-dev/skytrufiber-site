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
    SELECT c.id, c.name, c.assigned_csr, c.last_active,
    (SELECT COUNT(*) FROM messages m 
        WHERE m.client_id = c.id AND m.sender_type='client' AND m.is_read=0) AS unread
    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY c.last_active DESC";

$stmt = $conn->prepare($sql);

if ($search !== "") {
    $stmt->execute([":search" => "%$search%"]);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread   = $row["unread"];
    $isMine   = ($assigned === $csrUser);

    $avatar   = "upload/default-avatar.png";

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <img src='$avatar' class='client-avatar'>
        
        <div class='client-content'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>
            <div class='client-sub'>
                " . ($assigned === null || $assigned === ""
                    ? "Unassigned"
                    : ($isMine ? "Assigned to YOU" : "Assigned to $assigned")
                ) . "
            </div>
        </div>

        <div class='client-action'>";
        
            if ($assigned === null || $assigned === "") {
                echo "<button class='pill green' onclick='event.stopPropagation(); assignClient($id)'>➕</button>";
            } elseif ($isMine) {
                echo "<button class='pill red' onclick='event.stopPropagation(); unassignClient($id)'>➖</button>";
            } else {
                echo "<button class='pill gray' disabled>🔒</button>";
            }

    echo "
        </div>
    </div>";
}
?>
