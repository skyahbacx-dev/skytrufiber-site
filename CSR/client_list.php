<?php
session_start();
include "../db_connect.php";

header("Content-Type: text/html; charset=UTF-8");

if (!isset($_SESSION["csr_user"])) {
    exit("<div style='padding:10px;color:red;'>Session expired — please login.</div>");
}

$csrUser = $_SESSION["csr_user"];

// SEARCH FILTER
$search = trim($_GET["search"] ?? "");

// SORT PRIORITY
// Assigned to YOU → Unassigned → Assigned to others
$sql = "
    SELECT id, name, assigned_csr, last_active
    FROM clients
";

if ($search !== "") {
    $sql .= " WHERE name ILIKE :search ";
}

$sql .= "
    ORDER BY
        (assigned_csr = :me) DESC,
        (assigned_csr IS NULL OR assigned_csr = '') DESC,
        last_active DESC
";

$stmt = $conn->prepare($sql);

if ($search !== "") {
    $stmt->bindValue(":search", "%$search%");
}
$stmt->bindValue(":me", $csrUser);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $active   = $row["last_active"] ? date("M d g:i A", strtotime($row["last_active"])) : "No activity";

    echo "<div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
            <div class='client-main'>
                <img src='upload/default-avatar.png' class='client-avatar'>
                <div>
                    <div class='client-name'>$name</div>
                    <div class='client-sub'>
                        " . ($assigned ? "Assigned to $assigned" : "Unassigned") . "
                    </div>
                </div>
            </div>

            <div class='client-actions'>";

    // ➕ if unassigned
    if ($assigned === null || $assigned === "") {
        echo "<button class='pill green' onclick='event.stopPropagation(); assignClient($id)'>➕</button>";
    }
    // ➖ if assigned to currently logged CSR
    elseif ($assigned === $csrUser) {
        echo "<button class='pill red' onclick='event.stopPropagation(); unassignClient($id)'>➖</button>";
    }
    // 🔒 assigned to others
    else {
        echo "<button class='pill gray' disabled title='Handled by $assigned'>🔒</button>";
    }

    echo "</div></div>";
}
?>
