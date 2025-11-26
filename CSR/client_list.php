<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser = $_SESSION["csr_user"];  // logged-in CSR
$search  = $_GET["search"] ?? "";

// SQL TO LOAD CLIENT LIST
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

$sql .= " ORDER BY unread DESC, u.full_name ASC";

$stmt = $conn->prepare($sql);

$params = [];
if ($search !== "") $params[":search"] = "%$search%";

$stmt->execute($params);

// loop results
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id       = $row["id"];
    $name     = htmlspecialchars($row["full_name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);

    /* ---- Determine button state ---- */
    if ($assigned === null || $assigned === "") {
        // NOT ASSIGNED — show assign button +
        $button = "
            <button class='assign-btn' onclick='event.stopPropagation(); assignClient($id)'>
                <i class='fa-solid fa-plus'></i>
            </button>";
    }
    else if ($assigned === $csrUser) {
        // assigned to current CSR — show remove button -
        $button = "
            <button class='unassign-btn' onclick='event.stopPropagation(); unassignClient($id)'>
                <i class='fa-solid fa-minus'></i>
            </button>";
    }
    else {
        // assigned to another CSR — show lock icon
        $button = "
            <button class='lock-btn' disabled>
                <i class='fa-solid fa-lock'></i>
            </button>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>

        <div class='client-info'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>
            <div class='client-sub'>
                District: {$row["district"]} | Brgy: {$row["barangay"]}
            </div>
        </div>

        <div class='client-actions'>
            $button
        </div>
    </div>
    ";
}
?>
