<?php
session_start();
include '../db_connect.php';

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
        (SELECT COUNT(*) FROM chat WHERE client_id = u.id AND sender_type = 'client' AND seen = false) AS unread
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

    $id = $row["id"];
    $name = htmlspecialchars($row["full_name"]);
    $email = htmlspecialchars($row["email"]);
    $assigned = $row["assigned_csr"];
    $unread = intval($row["unread"]);

    $lockIcon = "";
    if ($assigned && $assigned != $csrUser) {
        $lockIcon = "<span class='lock-icon'>🔒</span>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-content'>
            <div class='client-name'>$name $lockIcon</div>
            <div class='client-email'>$email</div>
            " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
        </div>
    </div>";
}
?>
