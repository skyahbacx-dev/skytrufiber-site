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

/* ================= GET USERS WITH UNREAD COUNT ================= */
$sql = "
    SELECT
        u.id AS user_id,
        u.full_name,
        u.assigned_csr,
        (
            SELECT COUNT(*)
            FROM chat m
            WHERE m.user_id = u.id
              AND m.sender_type = 'client'
              AND m.seen = false
        ) AS unread
    FROM users u
";

if ($search !== "") {
    $sql .= " WHERE LOWER(u.full_name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, u.full_name ASC";

$stmt = $conn->prepare($sql);

$params = [];
if ($search !== "") $params[":search"] = "%$search%";

$stmt->execute($params);

/* ================= RENDER LIST ================= */
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $user_id  = $row["user_id"];
    $name     = htmlspecialchars($row["full_name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);

    /* ACTION BUTTON CONDITIONS */
    if ($assigned === null || $assigned === "") {
        $btn = "<button class='assign-btn' onclick='event.stopPropagation(); showAssignPopup($user_id)'><i class=\"fa-solid fa-plus\"></i></button>";
    } elseif ($assigned === $csrUser) {
        $btn = "<button class='unassign-btn' onclick='event.stopPropagation(); showUnassignPopup($user_id)'><i class=\"fa-solid fa-minus\"></i></button>";
    } else {
        $btn = "<button class='lock-btn' disabled><i class=\"fa-solid fa-lock\"></i></button>";
    }

    echo "
    <div class='client-item' id='client-$user_id' onclick='selectClient($user_id, \"$name\", \"$assigned\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-content'>
            <div class='client-name'>
                $name ". ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>
            <div class='client-sub'>
                " . ($assigned === null ? "Unassigned"
                    : ($assigned === $csrUser ? "Assigned to YOU"
                    : "Assigned to $assigned")) . "
            </div>
        </div>
        <div class='client-actions'>$btn</div>
    </div>";
}
?>
