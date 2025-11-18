<?php
// CSR/client_list.php
session_start();
require_once "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? "";

$search = trim($_GET["search"] ?? "");

// Build query (show ALL clients; search by name)
$where = "";
$params = [];

if ($search !== "") {
    $where = "WHERE name ILIKE :search";
    $params[":search"] = "%" . $search . "%";
}

$sql = "
    SELECT id, name, assigned_csr, last_active
    FROM clients
    $where
    ORDER BY last_active DESC NULLS LAST, name ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id        = (int)$row["id"];
    $name      = htmlspecialchars($row["name"]);
    $assigned  = $row["assigned_csr"];
    $isMine    = ($assigned && $csrUser && $assigned === $csrUser);
    $isFree    = ($assigned === null || $assigned === "");
    $statusTxt = $isFree ? "Unassigned" : "Assigned to $assigned";

    echo "<div class='client-item' id='client-{$id}' onclick='selectClient({$id}, \"{$name}\", " . json_encode($assigned) . ")'>
            <div class='client-main'>
                <img src='upload/default-avatar.png' class='client-avatar'>
                <div>
                    <div class='client-name'>{$name}</div>
                    <div class='client-sub'>{$statusTxt}</div>
                </div>
            </div>
            <div class='client-actions'>";

    // plus / minus / lock logic
    if ($isFree) {
        echo "<button class='pill pill-green' onclick='event.stopPropagation(); assignClient({$id})'>➕</button>";
    } elseif ($isMine) {
        echo "<button class='pill pill-red' onclick='event.stopPropagation(); unassignClient({$id})'>➖</button>";
    } else {
        echo "<button class='pill pill-gray' title='Handled by {$assigned}' disabled>🔒</button>";
    }

    echo "  </div>
          </div>";
}
