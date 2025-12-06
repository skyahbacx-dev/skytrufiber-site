<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) {
    exit("No client selected.");
}

$csrUser = $_SESSION["csr_user"] ?? null;

try {
    $stmt = $conn->prepare("
        SELECT 
            id, 
            full_name, 
            email, 
            district, 
            barangay, 
            is_online, 
            assigned_csr,
            is_locked
        FROM users
        WHERE id = :cid
        LIMIT 1
    ");
    $stmt->execute([":cid" => $clientID]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$c) {
        exit("Client not found.");
    }

    $onlineStatus = $c["is_online"]
        ? "<span style='color:green;'>Online</span>"
        : "<span style='color:gray;'>Offline</span>";

    $lockedStatus = $c["is_locked"] ? "Locked" : "Unlocked";

    // --- PERMISSION LOGIC FLAGS ---
    // assigned to THIS csr?
    $isAssignedToMe = (!empty($c["assigned_csr"]) && $csrUser && $c["assigned_csr"] === $csrUser);
    // locked?
    $isLocked = (bool)$c["is_locked"];

    echo "
        <p><strong>Name:</strong> " . htmlspecialchars($c['full_name']) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($c['email']) . "</p>
        <p><strong>District:</strong> " . htmlspecialchars($c['district']) . "</p>
        <p><strong>Barangay:</strong> " . htmlspecialchars($c['barangay']) . "</p>
        <p><strong>Status:</strong> $onlineStatus</p>
        <p><strong>Lock State:</strong> $lockedStatus</p>

        <!-- Hidden meta for JS permission handling -->
        <div id='client-meta'
             data-assigned='" . ($isAssignedToMe ? "yes" : "no") . "'
             data-locked='" . ($isLocked ? "true" : "false") . "'
        ></div>
    ";

} catch (PDOException $e) {
    echo "DB Error: " . htmlspecialchars($e->getMessage());
}
