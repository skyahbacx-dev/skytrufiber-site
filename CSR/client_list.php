<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? "";
$search  = $_GET["search"] ?? "";

// Fetch clients assigned or unassigned
$sql = "
    SELECT c.id, c.name, c.assigned_csr,
        (SELECT COUNT(*) FROM chat_read r
         JOIN chat m ON m.id = r.chat_id
         WHERE r.csr = :csr AND r.client_id = c.id AND r.last_read < m.created_at
        ) AS unread_count
    FROM clients c
    WHERE c.name ILIKE :search
    ORDER BY c.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":csr" => $csrUser,
    ":search" => "%$search%"
]);

$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($clients as $c) {
    $id = $c["id"];
    $unread = intval($c["unread_count"]);
    $assigned = $c["assigned_csr"];
    $badge = ($unread > 0) ? "<span class='badge'>$unread</span>" : "";

    $statusText = ($assigned === $csrUser)
        ? "Assigned to YOU"
        : ($assigned ? "Assigned to $assigned" : "Unassigned");

    $button = "";
    if ($assigned === $csrUser) {
        $button = "<button class='assign-btn minus' onclick='showUnassignPopup($id)'>−</button>";
    } elseif (!$assigned) {
        $button = "<button class='assign-btn plus' onclick='showAssignPopup($id)'>+</button>";
    } else {
        $button = "<button class='assign-btn lock' disabled>🔒</button>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$c[name]\", \"$assigned\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-meta'>
            <div class='client-name'>$c[name] $badge</div>
            <div class='client-status'>$statusText</div>
        </div>
        <div class='client-assign'>$button</div>
    </div>
    ";
}
?>
