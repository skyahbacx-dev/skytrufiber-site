<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) {
    exit("No client selected.");
}

$currentCSR = $_SESSION["csr_user"] ?? null;

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

    // Status formatting
    $onlineStatus = $c["is_online"] ? "<span style='color:green;'>Online</span>" : "<span style='color:gray;'>Offline</span>";
    $lockedStatus = $c["is_locked"] ? "Locked" : "Unlocked";

    // === Permission Flags ===
    $isAssignedToMe = ($c["assigned_csr"] === $currentCSR) ? "yes" : "no";
    $isLocked       = $c["is_locked"] ? "true" : "false";

    echo "
        <p><strong>Name:</strong> {$c['full_name']}</p>
        <p><strong>Email:</strong> {$c['email']}</p>
        <p><strong>District:</strong> {$c['district']}</p>
        <p><strong>Barangay:</strong> {$c['barangay']}</p>
        <p><strong>Status:</strong> $onlineStatus</p>
        <p><strong>Lock State:</strong> $lockedStatus</p>

        <!-- Hidden Meta: CSR Permissions -->
        <div id='client-meta'
            data-assigned='$isAssignedToMe'
            data-locked='$isLocked'>
        </div>
    ";

} catch (PDOException $e) {
    echo "DB Error: " . htmlspecialchars($e->getMessage());
}
?>
