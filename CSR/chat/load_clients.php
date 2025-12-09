<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$filter = $_POST["filter"] ?? "all";

// ============================================================
// FETCH ALL CLIENTS + THEIR TICKET STATUS
// ============================================================
$query = "
    SELECT 
        u.id,
        u.full_name,
        u.assigned_csr,
        u.ticket_status,
        u.ticket_lock,
        u.last_message,
        u.email
    FROM users u
    ORDER BY u.full_name ASC
";
$stmt = $conn->query($query);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "<p style='text-align:center;color:#888;margin-top:20px;'>No clients found.</p>";
    exit;
}

foreach ($clients as $c) {

    $id     = $c["id"];
    $name   = htmlspecialchars($c["full_name"]);
    $status = strtolower($c["ticket_status"]);
    $assigned = $c["assigned_csr"] ?: "None";
    $locked   = $c["ticket_lock"] == 1;

    // FILTER RULES
    if ($filter !== "all" && $filter !== $status) {
        continue;
    }

    // Status Dot Color
    $statusDot = match ($status) {
        "resolved"   => "color:#26a65b;",
        "pending"    => "color:#f5a623;",
        "unresolved" => "color:#d63031;",
        default      => "color:#999;"
    };

    // Ticket Status Label
    $statusLabel = ucfirst($status);

    // Last Message Preview
    $lastMsg = $c["last_message"] 
        ? htmlspecialchars(substr($c["last_message"], 0, 40)) . "..."
        : "No messages yet";

    // LOCK ICON
    $lockIcon = $locked 
        ? "<i class='fa-solid fa-lock' style='color:#ff3b3b;font-size:14px;'></i>"
        : "<i class='fa-solid fa-lock-open' style='color:#999;font-size:14px;'></i>";

    // ASSIGN / UNASSIGN BUTTON
    $actionBtn = "";
    $currentCSR = $_SESSION["csr_user"] ?? "";

    if ($c["assigned_csr"] === NULL) {
        // Show ADD button for unassigned client
        $actionBtn = "
            <button class='assign-btn' data-id='{$id}' title='Assign to me'>
                <i class='fa-solid fa-user-plus'></i>
            </button>
        ";
    } else {
        // Assigned to someone → show REMOVE button
        $actionBtn = "
            <button class='unassign-btn' data-id='{$id}' title='Unassign CSR'>
                <i class='fa-solid fa-user-minus'></i>
            </button>
        ";
    }

    // BUILD CLIENT ITEM ROW
    echo "
    <div class='client-item' data-id='{$id}' data-name='{$name}'>
        
        <div class='client-info'>
            <strong>{$name}</strong>
            <span class='last-msg'>{$lastMsg}</span>
            
            <span class='ticket-dot' style='{$statusDot}'>&#9679;</span>
            <span style='font-size:12px;color:#555;'> {$statusLabel}</span>
            
            <div style='font-size:11px;color:#777;'>
                Assigned to: <strong>{$assigned}</strong>
                &nbsp;&nbsp; $lockIcon
            </div>
        </div>

        <div class='client-icons'>
            $actionBtn
        </div>

    </div>
    ";
}
?>
