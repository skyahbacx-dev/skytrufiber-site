<?php
session_start();
include "../db_connect.php";
header("Content-Type: text/html; charset=UTF-8");

$search = $_GET["search"] ?? "";
$csrUser = $_SESSION["csr_user"] ?? "";

// MAIN QUERY — fetch clients and assignment
$sql = "
SELECT 
    c.id, 
    c.name,
    c.assigned_csr,
    c.last_active,
    COALESCE(
        (SELECT message FROM chat 
         WHERE client_id = c.id 
         ORDER BY created_at DESC LIMIT 1
        ), ''
    ) AS last_msg,
    COALESCE(
        (SELECT created_at FROM chat 
         WHERE client_id = c.id 
         ORDER BY created_at DESC LIMIT 1
        ), ''
    ) AS last_msg_time
FROM clients c
WHERE c.name ILIKE :search
ORDER BY c.last_active DESC NULLS LAST
";

$stmt = $conn->prepare($sql);
$stmt->execute([":search" => "%$search%"]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "<p class='no-clients'>No clients found...</p>";
    exit;
}

foreach ($clients as $c) {

    $active = (strtotime($c["last_active"]) > strtotime("-2 minutes")) ? "online" : "offline";
    $assignedToYou = ($c["assigned_csr"] === $csrUser);
    $assignedToOther = ($c["assigned_csr"] && $c["assigned_csr"] !== $csrUser);

    echo "<div class='client-item' id='client-{$c["id"]}' onclick=\"selectClient({$c["id"]}, '{$c["name"]}', '{$c["assigned_csr"]}')\">";

    echo "<img src='upload/default-avatar.png' class='client-avatar'>";

    echo "<div class='client-meta'>
            <div class='client-name'>{$c["name"]}</div>
            <div class='client-status $active'>" . ucfirst($active) . "</div>";

    if ($assignedToYou) {
        echo "<div class='assign-label you'>Assigned to YOU</div>";
    } elseif ($assignedToOther) {
        echo "<div class='assign-label other'>Assigned to {$c['assigned_csr']}</div>";
    } else {
        echo "<div class='assign-label none'>Not assigned</div>";
    }

    echo "</div>";

    // ASSIGN / UNASSIGN / LOCK BUTTON LOGIC
    echo "<div class='assign-actions'>";
    if ($assignedToYou) {
        echo "<button class='assign-btn unassign' onclick=\"event.stopPropagation(); showUnassignPopup({$c["id"]})\">−</button>";
    } elseif ($assignedToOther) {
        echo "<button class='assign-btn lock' disabled>🔒</button>";
    } else {
        echo "<button class='assign-btn assign' onclick=\"event.stopPropagation(); showAssignPopup({$c["id"]})\">+</button>";
    }
    echo "</div>";

    echo "</div>";
}
?>
