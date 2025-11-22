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

    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);

    $assignBtn = "";
    if (!$assigned) {
        $assignBtn = "<button class='assign-btn' onclick='showAssignPopup({$row['id']})'>+</button>";
    } elseif ($assigned === $csr) {
        $assignBtn = "<button class='assign-btn mine' onclick='showUnassignPopup({$row['id']})'>−</button>";
    } else {
        $assignBtn = "<button class='assign-btn lock' disabled><i class='fa fa-lock'></i></button>";
    }

    echo "
    <div class='client-item' id='client-{$row['id']}' onclick=\"selectClient({$row['id']}, '{$row['name']}', '{$assigned}')\">
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-info'>
            <div class='client-name'>{$row['name']}</div>
            <div class='client-assigned'>Assigned to: {$assigned}</div>
        </div>
        <div class='client-actions'>
            $assignBtn
            " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
        </div>
    </div>";
}
?>
