<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) {
    exit("No client selected.");
}

$currentCSR = $_SESSION["csr_user"] ?? "";

// Ensure safe string for comparison (prevents deprecated warnings)
$currentCSR = $currentCSR ?? "";
$currentCSR = trim($currentCSR);

try {

    $stmt = $conn->prepare("
        SELECT 
            id,
            full_name,
            email,
            account_number,
            district,
            barangay,
            is_online,
            assigned_csr,
            is_locked,
            ticket_status
        FROM users
        WHERE id = :cid
        LIMIT 1
    ");
    $stmt->execute([":cid" => $clientID]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$c) {
        exit("Client not found.");
    }

    // Escape values safely
    $fullName  = htmlspecialchars($c['full_name']);
    $email     = htmlspecialchars($c['email']);
    $acctNum   = htmlspecialchars($c['account_number']);
    $district  = htmlspecialchars($c['district']);
    $barangay  = htmlspecialchars($c['barangay']);

    // Normalize assigned CSR to avoid NULL warnings
    $assignedCSR = trim($c["assigned_csr"] ?? "");

    // --------------------------------------------------------
    // ONLINE STATUS
    // --------------------------------------------------------
    $onlineStatus = $c["is_online"]
        ? "<span style='color:green;'>Online</span>"
        : "<span style='color:gray;'>Offline</span>";

    // --------------------------------------------------------
    // LOCK STATUS
    // --------------------------------------------------------
    $lockedStatus = ($assignedCSR !== "" && $assignedCSR !== $currentCSR)
        ? "Locked"
        : "Unlocked";

    // --------------------------------------------------------
    // ASSIGNMENT FLAGS — Safe string comparison
    // --------------------------------------------------------
    $isAssignedToMe = ($assignedCSR !== "" && strcasecmp($assignedCSR, $currentCSR) === 0)
        ? "yes"
        : "no";

    $isLocked = ($assignedCSR !== "" && strcasecmp($assignedCSR, $currentCSR) !== 0)
        ? "true"
        : "false";

    // --------------------------------------------------------
    // TICKET STATUS
    // --------------------------------------------------------
    $ticketValue = $c["ticket_status"] ?? "unresolved";

    $ticketLabel = ($ticketValue === "resolved")
        ? "<span style='color:green;font-weight:bold;'>Resolved</span>"
        : "<span style='color:red;font-weight:bold;'>Unresolved</span>";

    $dropdownDisabled = ($isAssignedToMe === "yes") ? "" : "disabled";

    // --------------------------------------------------------
    // OUTPUT
    // --------------------------------------------------------
    echo "
        <p><strong>Name:</strong> $fullName</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Account Number:</strong> $acctNum</p>
        <p><strong>District:</strong> $district</p>
        <p><strong>Barangay:</strong> $barangay</p>
        <p><strong>Status:</strong> $onlineStatus</p>
        <p><strong>Lock State:</strong> $lockedStatus</p>

        <hr>

        <p><strong>Ticket Status:</strong> $ticketLabel</p>

        <div>
            <select id='ticket-status-dropdown'
                data-id='{$c['id']}'
                style='padding:6px;width:150px;'
                $dropdownDisabled>
                <option value='unresolved' " . ($ticketValue === "unresolved" ? "selected" : "") . ">Unresolved</option>
                <option value='resolved' " . ($ticketValue === "resolved" ? "selected" : "") . ">Resolved</option>
            </select>
        </div>

        <!-- META for chat.js -->
        <div id='client-meta'
            data-assigned='$isAssignedToMe'
            data-locked='$isLocked'
            data-ticket='$ticketValue'>
        </div>
    ";

} catch (PDOException $e) {
    echo 'DB Error: ' . htmlspecialchars($e->getMessage());
}
?>
