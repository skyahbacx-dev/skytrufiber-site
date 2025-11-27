<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
if (!$csrUser) {
    echo "<p style='padding:10px;color:red;'>CSR not authenticated.</p>";
    exit;
}

try {
    // Fetch all clients
    $stmt = $conn->prepare("
        SELECT id, full_name, email, assigned_csr, is_locked
        FROM users
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$clients) {
        echo "<p style='padding:10px;'>No clients found.</p>";
        exit;
    }

    foreach ($clients as $c) {

        // Determine icon visibility rules
        $showAdd   = ($c["assigned_csr"] === null);              // unassigned only
        $showMinus = ($c["assigned_csr"] === $csrUser);          // assigned to this CSR only
        $isLocked  = ($c["is_locked"] == 1);                     // locked state

        // Build icons row
        $icons = "<div class='client-actions'>";

        if ($showAdd) {
            $icons .= "<button class='icon-btn add' onclick='assignClient({$c["id"]}); event.stopPropagation();'><i class=\"fa fa-plus\"></i></button>";
        }

        if ($showMinus) {
            $icons .= "<button class='icon-btn minus' onclick='unassignClient({$c["id"]}); event.stopPropagation();'><i class=\"fa fa-minus\"></i></button>";
        }

        // 🔒 always shows (lock logic independent of assignment)
        $icons .= "<button class='icon-btn lock' onclick='lockClient({$c["id"]}); event.stopPropagation();'><i class=\"fa fa-lock\"></i></button>";

        $icons .= "</div>";

        // Locked styling
        $lockClass = ($isLocked ? "locked-client" : "");

        echo "
        <div class='client-item $lockClass' onclick=\"selectClient({$c["id"]}, '" . htmlspecialchars($c["full_name"], ENT_QUOTES) . "')\">
            <div class='client-info'>
                <strong>{$c["full_name"]}</strong>
                <small>{$c["email"]}</small>
            </div>
            $icons
        </div>
        ";
    }

} catch (PDOException $e) {
    echo "DB Error: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
