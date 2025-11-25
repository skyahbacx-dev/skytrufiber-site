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

/* ============================================================
   Client List Query using USERS table (not clients)
   ============================================================ */
$sql = "
    SELECT
        u.id,
        u.fullname AS name,
        u.assigned_csr,
        (
            SELECT COUNT(*)
            FROM chat m
            WHERE m.client_id = u.id
              AND m.sender_type = 'client'
              AND m.seen = false
        ) AS unread
    FROM users u
";

$params = [];

if ($search !== "") {
    $sql .= " WHERE LOWER(u.fullname) LIKE LOWER(:search)";
    $params[\":search\"] = \"%$search%\";
}

$sql .= " ORDER BY unread DESC, u.fullname ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

/* ============================================================
   OUTPUT CLIENT LIST ITEMS
   ============================================================ */
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);

    // Action button depending on assignment
    if ($assigned === null) {
        $btn = "<button class='assign-btn' onclick='event.stopPropagation(); showAssignPopup($id)'>
                    <i class='fa-solid fa-plus'></i>
                </button>";
    } elseif ($assigned === $csrUser) {
        $btn = "<button class='unassign-btn' onclick='event.stopPropagation(); showUnassignPopup($id)'>
                    <i class='fa-solid fa-minus'></i>
                </button>";
    } else {
        $btn = "<button class='lock-btn' disabled>
                    <i class='fa-solid fa-lock'></i>
                </button>";
    }

    echo "
    <div class='client-item' id='client-$id'
         onclick='selectClient($id, \"$name\", \"$assigned\")'>
         
        <img src='../SKYTRUFIBER.png' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
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
