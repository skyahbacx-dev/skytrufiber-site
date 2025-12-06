<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csrID = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;

if (!$csrID) {
    http_response_code(403);
    exit("Unauthorized");
}
if (!$clientID) {
    exit("No client selected.");
}

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

    if (!$c) exit("Client not found.");

    // Escape output
    $name     = htmlspecialchars($c["full_name"]);
    $email    = htmlspecialchars($c["email"]);
    $district = htmlspecialchars($c["district"]);
    $barangay = htmlspecialchars($c["barangay"]);

    $onlineStatus = $c["is_online"]
        ? "<span style='color:green;'>Online</span>"
        : "<span style='color:gray;'>Offline</span>";

    $lockedStatus = $c["is_locked"] ? "Locked" : "Unlocked";

    // Who owns this client?
    $assignedCSR = $c["assigned_csr"];
    $isMine      = ($assignedCSR == $csrID);
    $isUnassigned = empty($assignedCSR);

    // -------------------------------
    // Hidden metadata for JS control
    // -------------------------------
    echo "
        <div id='client-meta'
             data-assigned='" . ($isMine ? "yes" : "no") . "'
             data-locked='" . ($c["is_locked"] ? "true" : "false") . "'>
        </div>
    ";

    // -------------------------------
    // Client Information Panel
    // -------------------------------
    echo "
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>District:</strong> $district</p>
        <p><strong>Barangay:</strong> $barangay</p>
        <p><strong>Status:</strong> $onlineStatus</p>
        <p><strong>Lock State:</strong> $lockedStatus</p>
    ";

} catch (PDOException $e) {
    echo "DB Error: " . htmlspecialchars($e->getMessage());
}
?>
