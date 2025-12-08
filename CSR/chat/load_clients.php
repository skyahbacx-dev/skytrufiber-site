<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
if (!$csr) {
    http_response_code(403);
    exit("Unauthorized");
}

// Filter: all / resolved / unresolved
$filter = $_POST["filter"] ?? "all";

try {

    /* ==========================================================
       FIXED WHERE CLAUSE (OPTION 1)
       - Show clients assigned to CSR
       - Also show ALL resolved clients (even unassigned)
    ========================================================== */

    $where = "
        WHERE (assigned_csr = :csr OR ticket_status = 'resolved')
    ";
    $params = [":csr" => $csr];

    if ($filter === "resolved") {
        $where .= " AND ticket_status = 'resolved'";
    }
    elseif ($filter === "unresolved") {
        $where .= " AND ticket_status = 'unresolved'";
    }

    /* ==========================================================
       QUERY CLIENT LIST (last message + account number)
    ========================================================== */
    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.account_number,
            u.is_online,
            u.assigned_csr,
            u.is_locked,
            u.ticket_status,

            COALESCE(
                (
                    SELECT CASE 
                        WHEN deleted = TRUE THEN 'Message removed'
                        ELSE message 
                    END
                    FROM chat
                    WHERE client_id = u.id
                    ORDER BY id DESC
                    LIMIT 1
                ),
                ''
            ) AS last_message

        FROM users u
        $where
        ORDER BY 
            (ticket_status = 'unresolved') DESC, 
            u.full_name ASC
    ");
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$clients) {
        echo "<p style='padding:10px;color:#777'>No clients found.</p>";
        exit;
    }

    /* ==========================================================
       RENDER CLIENT LIST
    ========================================================== */
    foreach ($clients as $c) {

        $id      = (int)$c["id"];
        $name    = htmlspecialchars($c["full_name"], ENT_QUOTES);
        $email   = htmlspecialchars($c["email"]);
        $acct    = htmlspecialchars($c["account_number"] ?? "N/A");
        $lastMsg = htmlspecialchars($c["last_message"]);
        $online  = $c["is_online"] ? "online" : "offline";

        $ticket    = $c["ticket_status"] ?? "unresolved";
        $assigned  = $c["assigned_csr"];

        /* ------------------------------------------------------
           TICKET STATUS ICON
        ------------------------------------------------------ */
        $ticketIcon = ($ticket === "resolved")
            ? "<span class='ticket-dot resolved'>✔</span>"
            : "<span class='ticket-dot unresolved'>●</span>";

        /* ------------------------------------------------------
           ACTION BUTTONS
        ------------------------------------------------------ */

        // Can only ADD if client is unassigned AND unresolved
        $showAdd = (empty($assigned) && $ticket !== "resolved");

        // CSR can REMOVE only if they are assigned AND unresolved
        $showRemove = ($assigned === $csr && $ticket !== "resolved");

        // Lock icon if assigned to someone else
        $showLock = (!empty($assigned) && $assigned !== $csr);

        $addBtn = $showAdd ? "
            <button class='client-action-btn add-client' data-id='$id'>
                <i class='fa fa-plus'></i>
            </button>" : "";

        $removeBtn = $showRemove ? "
            <button class='client-action-btn remove-client' data-id='$id'>
                <i class='fa fa-minus'></i>
            </button>" : "";

        $lockBtn = $showLock ? "
            <button class='client-action-btn lock-client' disabled>
                <i class='fa fa-lock'></i>
            </button>" : "";

        /* ------------------------------------------------------
           OUTPUT ENTRY
        ------------------------------------------------------ */
        echo "
        <div class='client-item'
             data-id='$id'
             data-name=\"$name\"
             data-ticket='$ticket'
        >

            <div class='client-status $online'></div>

            <div class='client-info'>
                <strong class='client-name'>$name $ticketIcon</strong>

                <small>$email</small>
                <small>Acct: $acct</small>

                <small class='last-msg'>$lastMsg</small>
            </div>

            <div class='client-icons'>
                $addBtn
                $removeBtn
                $lockBtn
            </div>

        </div>";
    }

} catch (PDOException $e) {
    echo "DB ERROR: " . htmlspecialchars($e->getMessage());
}
?>
