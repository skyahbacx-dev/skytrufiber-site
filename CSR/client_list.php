<?php
session_start();
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
        u.email,
        u.district,
        u.barangay,
        u.assigned_csr,
        (
            SELECT COUNT(*)
            FROM chat c
            WHERE c.client_id = u.id
              AND c.sender_type = 'client'
              AND c.seen = FALSE
        ) AS unread
    FROM users u
    WHERE u.full_name ILIKE :search
    ORDER BY unread DESC, full_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([":search" => "%$search%"]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["full_name"]);
    $email    = htmlspecialchars($row["email"]);
    $unread   = intval($row["unread"]);
    $assigned = $row["assigned_csr"];

    $badge = ($unread > 0) ? "<span class='badge'>$unread</span>" : "";

    // BUTTON LOGIC
    if ($assigned === null) {
        $btn = "<button class='assign-btn' onclick='assignClient($id); event.stopPropagation();'>Assign</button>";
    } elseif ($assigned === $csrUser) {
        $btn = "<button class='unassign-btn' onclick='unassignClient($id); event.stopPropagation();'>Unassign</button>";
    } else {
        $btn = "<button class='lock-btn' disabled>🔒</button>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-content'>
            <div class='client-name'>$name $badge</div>
            <div class='client-sub'>District: {$row["district"]} | Brgy: {$row["barangay"]}</div>
        </div>
        <div class='client-actions'>$btn</div>
    </div>
    ";
}
?>
