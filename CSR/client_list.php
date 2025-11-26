<?php
;
include '../db_connect.php';

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];
$search  = $_GET["search"] ?? "";

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.district,
        u.barangay,
        u.assigned_csr,
        (
            SELECT COUNT(*)
            FROM chat c
            WHERE c.user_id = u.id
              AND c.sender_type = 'client'
              AND c.seen = false
        ) AS unread
    FROM users u
";

if ($search !== "") {
    $sql .= " WHERE LOWER(u.full_name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, full_name ASC";

$stmt = $conn->prepare($sql);

if ($search !== "") $stmt->execute([":search" => "%$search%"]);
else $stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["full_name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);

    if ($assigned === null || $assigned === "") {
        $btn = "<button class='assign-btn' onclick='event.stopPropagation(); showAssignPopup($id)'><i class=\"fa-solid fa-plus\"></i></button>";
    } elseif ($assigned === $csrUser) {
        $btn = "<button class='unassign-btn' onclick='event.stopPropagation(); showUnassignPopup($id)'><i class=\"fa-solid fa-minus\"></i></button>";
    } else {
        $btn = "<button class='lock-btn' disabled><i class=\"fa-solid fa-lock\"></i></button>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-content'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>
            <div class='client-sub'>District: {$row["district"]} | Brgy: {$row["barangay"]}</div>
        </div>
        <div class='client-actions'>$btn</div>
    </div>
    ";
}
?>
