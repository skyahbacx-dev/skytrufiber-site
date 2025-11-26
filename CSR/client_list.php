<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];

$search = $_GET["search"] ?? "";

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
";

if ($search !== "") {
    $sql .= " WHERE LOWER(u.full_name) LIKE LOWER(:search) ";
}

$sql .= " ORDER BY unread DESC, full_name ASC";

$stmt = $conn->prepare($sql);

if ($search !== "") {
    $stmt->execute([":search" => "%$search%"]);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id    = $row["id"];
    $name  = htmlspecialchars($row["full_name"]);
    $email = htmlspecialchars($row["email"]);
    $assigned = $row["assigned_csr"];
    $unread   = (int) $row["unread"];

    // Determine lock icon
    $locked = ($assigned && $assigned !== $csrUser);
    $icon = $locked ? "🔒" : "➕";

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>
                $name 
                " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>
            <div class='client-sub'>
                $email
            </div>
        </div>

        <button class='assign-btn' onclick='event.stopPropagation(); " . 
            ($locked ? "showUnassignPopup($id)" : "showAssignPopup($id)") . "'>
            $icon
        </button>
    </div>
    ";
}
?>
