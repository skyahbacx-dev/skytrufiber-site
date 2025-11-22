<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? "";
$search = $_GET["search"] ?? "";

if (!$csr) exit("Unauthorized");

$sql = "
SELECT
    c.id,
    c.name,
    c.assigned_csr,
    COALESCE((
        SELECT COUNT(*)
        FROM chat m
        LEFT JOIN chat_read r ON r.chat_id = m.id AND r.csr = :csr
        WHERE m.client_id = c.id
        AND m.sender_type = 'client'
        AND (r.last_read IS NULL OR m.created_at > r.last_read)
    ), 0) AS unread
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

    // Assign button logic
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

        <div class='client-actions'>
            $button
        </div>
    </div>";
}
?>
