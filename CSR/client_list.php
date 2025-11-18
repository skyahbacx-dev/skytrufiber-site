<?php
session_start();
if (!isset($_SESSION["csr_user"])) {
    die("Unauthorized");
}
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"];
$search = $_GET["search"] ?? "";

$stmt = $conn->prepare("
    SELECT id, name, assigned_csr, last_active
    FROM clients
    WHERE name ILIKE :s
    ORDER BY last_active DESC
");
$stmt->execute([":s" => "%$search%"]);

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $r["id"];
    $name = htmlspecialchars($r["name"]);
    $assigned = $r["assigned_csr"];

    echo "<div id='client-$id' class='client-item' onclick='selectClient($id, \"$name\", \"$assigned\")'>
            <div class='client-main'>
                <img src='upload/default-avatar.png' class='client-avatar'>
                <div>
                    <div class='client-name'>$name</div>
                    <div class='client-sub'>".
                        ($assigned ? "Assigned to $assigned" : "Unassigned")
                    ."</div>
                </div>
            </div>
            <div class='client-actions'>";

    if ($assigned === null || $assigned === "") {
        echo "<button class='pill green' onclick='event.stopPropagation();assignClient($id)'>➕</button>";
    }
    elseif ($assigned === $csrUser) {
        echo "<button class='pill red' onclick='event.stopPropagation();unassignClient($id)'>➖</button>";
    }
    else {
        echo "<button class='pill gray' disabled>🔒</button>";
    }

    echo "</div></div>";
}
?>
