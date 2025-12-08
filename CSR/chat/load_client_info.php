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
    $district  = htmlspecialchars($c['district']);
    $barangay  = htmlspecialchars($c['barangay']);

    // Online / Offline badge
    $onlineStatus = $c["is_online"]
        ? "<span style='color:green;'>Online</span>"
        : "<span style='color:gray;'>Offline</span>";

    // Lock State
    $lockedStatus = ($c["assigned_csr"] !== $currentCSR && !empty($c["assigned_csr"]))
        ? "Locked"
        : "Unlocked";

    // Permission Flags
    $isAssignedToMe = ($c["assigned_csr"] === $currentCSR) ? "yes" : "no";
    $isLocked       = ($c["assigned_csr"] !== $currentCSR && !empty($c["assigned_csr"]))
        ? "true"
        : "false";

    // =============================
    // TICKET STATUS (Dropdown Version)
    // =============================
    $ticketValue = $c["ticket_status"] ?? "unresolved";

    $ticketDropdown = "
        <select id='ticket-status-dropdown' data-id='{$c['id']}' style='padding:6px;width:150px;'>
            <option value='unresolved' " . ($ticketValue === "unresolved" ? "selected" : "") . ">Unresolved</option>
            <option value='resolved' "   . ($ticketValue === "resolved"   ? "selected" : "") . ">Resolved</option>
        </select>
    ";

    // Label
    $ticketLabel = $ticketValue === "resolved"
        ? "<span style='color:green;font-weight:bold;'>Resolved</span>"
        : "<span style='color:red;font-weight:bold;'>Unresolved</span>";

    // Disable dropdown if CSR is NOT assigned
    $dropdownDisabled = ($isAssignedToMe === "yes") ? "" : "disabled";

    // =============================
    // QUICK SUGGESTION BUTTONS
    // =============================
    $quickReplies = "
        <div style='margin-top:15px;'>
            <p><strong>Quick Replies:</strong></p>
            <button class='qs-btn' style='padding:6px;margin:3px;'>No Internet</button>
            <button class='qs-btn' style='padding:6px;margin:3px;'>Slow Connection</button>
            <button class='qs-btn' style='padding:6px;margin:3px;'>Router Restart</button>
            <button class='qs-btn' style='padding:6px;margin:3px;'>Checking Line</button>
            <button class='qs-btn' style='padding:6px;margin:3px;'>Please wait, verifying...</button>
        </div>
    ";

    // =============================
    // OUTPUT PANEL
    // =============================
    echo "
        <p><strong>Name:</strong> $fullName</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>District:</strong> $district</p>
        <p><strong>Barangay:</strong> $barangay</p>
        <p><strong>Status:</strong> $onlineStatus</p>
        <p><strong>Lock State:</strong> $lockedStatus</p>

        <hr>

        <p><strong>Ticket Status:</strong> $ticketLabel</p>
        <div>
            <select id='ticket-status-dropdown' data-id='{$c['id']}' style='padding:6px;width:150px;' $dropdownDisabled>
                <option value='unresolved' " . ($ticketValue === "unresolved" ? "selected" : "") . ">Unresolved</option>
                <option value='resolved'   " . ($ticketValue === "resolved"   ? "selected" : "") . ">Resolved</option>
            </select>
        </div>

        $quickReplies

        <!-- Hidden Meta -->
        <div id='client-meta'
             data-assigned='$isAssignedToMe'
             data-locked='$isLocked'>
        </div>
    ";

} catch (PDOException $e) {
    echo "DB Error: " . htmlspecialchars($e->getMessage());
}
?>
