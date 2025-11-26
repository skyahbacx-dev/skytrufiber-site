<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$currentCSR = $_SESSION["csr_user"];
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
            SELECT COUNT(*) FROM chat c
            WHERE c.client_id = u.id
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
    $clientID = $row["id"];
    $name = htmlspecialchars($row["full_name"]);
    $email = htmlspecialchars($row["email"]);
    $district = htmlspecialchars($row["district"]);
    $brgy = htmlspecialchars($row["barangay"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);

    // ICON LOGIC
    if ($assigned === null || $assigned === "0") {
        $icon = "<span class='assign-btn' onclick='assignClient($clientID); event.stopPropagation();'>➕</span>";
    } elseif ($assigned == $currentCSR) {
        $icon = "<span class='assign-btn remove' onclick='unassignClient($clientID); event.stopPropagation();'>➖</span>";
    } else {
        $icon = "<span class='assign-btn locked'>🔒</span>";
    }

    echo "
    <div class='client-item' id='client-$clientID' onclick='selectClient($clientID, \"$name\")'>
        
        <img src=\"upload/default-avatar.png\" class='client-avatar'>

        <div class='client-info'>
            <div class='client-name'>$name</div>
            <div class='client-email'>$email</div>
            <div class='client-meta'>Dist: $district | Brgy: $brgy</div>
        </div>

        <div class='right'>
            $icon
            " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
        </div>

    </div>
    ";
}
?>
