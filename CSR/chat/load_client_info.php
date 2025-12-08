<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) {
    exit("No client selected.");
}

$currentCSR = $_SESSION["csr_user"] ?? "";
$currentCSR = trim((string)$currentCSR);

try {
    $stmt = $conn->prepare("
        SELECT 
            id, 
            full_name, 
            email, 
            district, 
            barangay, 
            account_number,     -- ✅ NEW FIELD
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

    /* ---------------- SAFE VALUES ---------------- */
    $assignedCSR   = trim((string)$c["assigned_csr"]);
    $ticketValue   = trim((string)$c["ticket_status"] ?? "unresolved");
    $accountNumber = htmlspecialchars($c["account_number"] ?? "N/A"); // ✅ Safe escaping

    $fullName = htmlspecialchars($c["full_name"]);
    $email    = htmlspecialchars($c["email"]);
    $district = htmlspecialchars($c["district"]);
    $barangay = htmlspecialchars($c["barangay"]);

    /* ---------------- Online Status ---------------- */
    $onlineStatus = $c["is_online"]
        ? "<span style='color:green;'>Online</span>"
        : "<span style='color:gray;'>Offline</span>";

    /* ---------------- Lock State Logic ---------------- */
    if ($assignedCSR !== "" && strcasecmp($assignedCSR, $currentCSR) !== 0) {
        $lockedStatus = "Locked";
        $isLocked = "true";
    } else {
        $lockedStatus = "Unlocked";
        $isLocked = "false";
    }

    /* ---------------- Assignment Check ---------------- */
    $isAssignedToMe = (strcasecmp($assignedCSR, $currentCSR) === 0) ? "yes" : "no";

    /* ---------------- Ticket Status ---------------- */
    $ticketLabel = ($ticketValue === "resolved")
        ? "<span style='color:green;font-weight:bold;'>Resolved</span>"
        : "<span style='color:red;font-weight:bold;'>Unresolved</span>";

    $dropdownDisabled = ($isAssignedToMe === "yes") ? "" : "disabled";

    /* ---------------- OUTPUT ---------------- */
    echo "
        <p><strong>Name:</strong> $fullName</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Account Number:</strong> $accountNumber</p>   <!-- ✅ Added -->
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
