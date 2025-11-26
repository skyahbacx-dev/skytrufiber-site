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

if ($search !== "") {
    $stmt->execute([":search" => "%$search%"]);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row["id"];
    $name = htmlspecialchars($row["full_name"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);

    // Button logic
    if ($assigned === null) {
        $btn = "<i class='fa-solid fa-plus assign-icon green' onclick='assignClient(event, $id)'></i>";
    } elseif ($assigned === $csrUser) {
        $btn = "<i class='fa-solid fa-minus assign-icon red' onclick='unassignClient(event, $id)'></i>";
    } else {
        $btn = "<i class='fa-solid fa-lock assign-icon lock'></i>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-info'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>
            <div class='client-sub'>
                District: {$row["district"]} • Brgy: {$row["barangay"]}
            </div>
        </div>
        $btn
    </div>";
}
?>
