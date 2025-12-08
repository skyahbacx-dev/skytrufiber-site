<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
if (!$csr) {
    http_response_code(403);
    exit("Unauthorized");
}

// Filter from chat.js buttons (all/resolved/unresolved)
$filter = $_POST["filter"] ?? "all";

try {

    // Build WHERE conditions
    $where = "WHERE assigned_csr = :csr";
    $params = [":csr" => $csr];

    if ($filter === "resolved") {
        $where .= " AND ticket_status = 'resolved'";
    }
    elseif ($filter === "unresolved") {
        $where .= " AND ticket_status = 'unresolved'";
    }

    // Main Query
    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.is_online,
            u.assigned_csr,
            u.is_locked,
            u.ticket_status,
            COALESCE(
                (SELECT message FROM chat 
                 WHERE client_id = u.id 
                 ORDER BY created_at DESC LIMIT 1),
                ''
            ) AS last_message
        FROM users u
        $where
        ORDER BY u.full_name ASC
    ");
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$clients) {
        echo "<p style='padding:10px;color:#777'>No clients found.</p>";
        exit;
    }

    foreach ($clients as $c) {

        // Clean values
        $id        = (int)$c["id"];
        $name      = htmlspecialchars($c["full_name"]);
        $email     = htmlspecialchars($c["email"]);
        $lastMsg   = htmlspecialchars($c["last_message"]);
        $online    = $c["is_online"] ? "online" : "offline";
        $assigned  = $c["assigned_csr"];
        $locked    = $c["is_locked"];
        $ticket    = $c["ticket_status"] ?? "unresolved";

        // ======== TICKET ICON ========
        $ticketIcon = ($ticket === "resolved")
            ? "<span class='ticket-dot resolved'>✔</span>"
            : "<span class='ticket-dot unresolved'>●</span>";

        // ======== ASSIGN BUTTON LOGIC ========
        $showAdd      = empty($assigned);
        $showRemove   = ($assigned === $csr);
        $showLockIcon = (!empty($assigned) && $assigned !== $csr);

        $addBtn    = $showAdd      ? "<button class='client-action-btn add-client' data-id='$id'><i class='fa fa-plus'></i></button>" : "";
        $removeBtn = $showRemove   ? "<button class='client-action-btn remove-client' data-id='$id'><i class='fa fa-minus'></i></button>" : "";
        $lockBtn   = $showLockIcon ? "<button class='client-action-btn lock-client' disabled><i class='fa fa-lock'></i></button>" : "";

        // ======== OUTPUT CLIENT ITEM ========
        echo "
        <div class='client-item' data-id='$id' data-name=\"$name\">
            
            <div class='client-status $online'></div>
            
            <div class='client-info'>
                <strong>$name $ticketIcon</strong>
                <small>$email</small>
                <small class='last-msg'>$lastMsg</small>
            </div>

            <div class='client-icons'>
                $addBtn
                $removeBtn
                $lockBtn
            </div>
        </div>
        ";
    }

} catch (PDOException $e) {
    echo 'DB ERROR: ' . htmlspecialchars($e->getMessage());
}
?>
