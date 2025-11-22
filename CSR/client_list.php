<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? "";
$search = $_GET["search"] ?? "";

if (!$csr) exit("Unauthorized");

/*
 We compute unread messages by comparing chat.created_at
 vs chat_read.last_read for csr+client combination
*/
$sql = "
SELECT
    c.id,
    c.name,
    c.assigned_csr,
    (
        SELECT COUNT(*)
        FROM chat m
        WHERE m.client_id = c.id
        AND m.sender_type = 'client'
        AND m.created_at > COALESCE(
            (SELECT r.last_read FROM chat_read r
             WHERE r.client_id = c.id AND r.csr = :csr),
            '2000-01-01'
        )
    ) AS unread
FROM clients c
WHERE c.name ILIKE :search
ORDER BY unread DESC, c.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":csr" => $csr,
    ":search" => "%$search%"
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);
    $avatar   = "upload/default-avatar.png";

    if (!$assigned) {
        $button = "<button class='assign-btn plus' onclick='showAssignPopup($id);event.stopPropagation();'>+</button>";
    } elseif ($assigned === $csr) {
        $button = "<button class='assign-btn minus' onclick='showUnassignPopup($id);event.stopPropagation();'>−</button>";
    } else {
        $button = "<button class='assign-btn lock' disabled><i class='fa fa-lock'></i></button>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick=\"selectClient($id, '$name', '$assigned')\">
        <img src='$avatar' class='client-avatar'>

        <div class='client-info'>
            <div class='client-name'>
                $name
                " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>
            <div class='client-sub'>
                " . ($assigned ? "Assigned to: $assigned" : "Unassigned") . "
            </div>
        </div>

        <div class='client-actions'>$button</div>
    </div>";
}
?>
