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

    // SAFE ESCAPED VALUES
    $fullName  = htmlspecialchars($c['full_name']);
    $email     = htmlspecialchars($c['email']);
    $district  = htmlspecialchars($c['district']);
    $barangay  = htmlspecialchars($c['barangay']);

    // Status formatting
    $onlineStatus = $c["is_online"] 
        ? "<span style='color:green;'>Online</span>" 
        : "<span style='color:gray;'>Offline</span>";

    $lockedStatus = ($c["assigned_csr"] !== $currentCSR && !empty($c["assigned_csr"])) 
        ? "Locked" 
        : "Unlocked";

    // === Ticket Status ===
    $ticketStatus = $c["ticket_status"] ?? "unresolved";

    if ($ticketStatus === "resolved") {
        $ticketLabel = "<span style='color:green;font-weight:bold;'>Resolved</span>";
        $ticketBtn = "<button class='ticket-btn' data-id='{$c['id']}' data-status='unresolved'>Mark Unresolved</button>";
    } else {
        $ticketLabel = "<span style='color:red;font-weight:bold;'>Unresolved</span>";
        $ticketBtn = "<button class='ticket-btn' data-id='{$c['id']}' data-status='resolved'>Resolve Ticket</button>";
    }

    // === Permission Flags ===
    $isAssignedToMe = ($c["assigned_csr"] === $currentCSR) ? "yes" : "no";
    $isLocked = ($c["assigned_csr"] !== $currentCSR && !empty($c["assigned_csr"])) 
        ? "true" 
        : "false";

    // OUTPUT PANEL
    echo "
        <p><strong>Name:</strong> $fullName</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>District:</strong> $district</p>
        <p><strong>Barangay:</strong> $barangay</p>
        <p><strong>Status:</strong> $onlineStatus</p>
        <p><strong>Lock State:</strong> $lockedStatus</p>

        <hr>

        <p><strong>Ticket Status:</strong> $ticketLabel</p>
        <div>$ticketBtn</div>

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
