<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$filter = $_POST["filter"] ?? "all"; // all | unresolved | pending | resolved

// ============================================================
// FETCH ALL CLIENTS + THEIR LATEST TICKET STATUS
// ============================================================
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.assigned_csr,
        u.ticket_status,
        u.ticket_lock,
        u.transfer_request,
        (
            SELECT message 
            FROM chat 
            WHERE client_id = u.id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS last_msg,
        (
            SELECT created_at 
            FROM chat 
            WHERE client_id = u.id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS last_msg_time
    FROM users u
    WHERE u.role = 'client'
    ORDER BY last_msg_time DESC NULLS LAST, u.full_name ASC
");
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "<p style='padding:10px;color:#888;'>No clients found.</p>";
    exit;
}

$currentCSR = $_SESSION["csr_user"] ?? "";

// ============================================================
// FILTER CLIENTS
// ============================================================
function passFilter($row, $filter) {
    if ($filter === "all") return true;
    if ($filter === $row["ticket_status"]) return true;
    return false;
}

// ============================================================
// RENDER CLIENT LIST
// ============================================================
foreach ($clients as $c):

    if (!passFilter($c, $filter)) continue;

    $id         = $c["id"];
    $name       = htmlspecialchars($c["full_name"]);
    $status     = $c["ticket_status"];
    $assigned   = $c["assigned_csr"];
    $lock       = $c["ticket_lock"];
    $transfer   = $c["transfer_request"];
    $lastMsg    = $c["last_msg"] ? htmlspecialchars($c["last_msg"]) : "No messages yet";
    $isMine     = ($assigned === $currentCSR);

    // CSS classes:
    $statusDotClass = $status === "resolved" ? "resolved" :
                      ($status === "pending" ? "pending" : "unresolved");

    $isLockedIcon = $lock ? "🔒" : "🔓";

    // Transfer alert icon
    $transferIcon = "";
    if ($transfer && $transfer !== $currentCSR) {
        $transferIcon = " <span style='color:#e67e22;font-size:13px;'>⚠ Transfer Request</span>";
    }

?>
    <div class="client-item" 
         data-id="<?= $id ?>" 
         data-name="<?= $name ?>">

        <!-- Avatar -->
        <div class="avatar-small">
            <img src="/upload/default-avatar.png">
        </div>

        <!-- Client Info -->
        <div class="client-info">
            <strong><?= $name ?></strong>

            <div class="last-msg">
                <?= $lastMsg ?>
            </div>

            <div class="ticket-info">
                <span class="ticket-dot <?= $statusDotClass ?>">
                    ● <?= ucfirst($status) ?>
                </span>

                <?php if ($status === "pending"): ?>
                    <span style="font-size:12px;color:#777;">
                        (On hold—tech coordination)
                    </span>
                <?php endif; ?>

                <?= $transferIcon ?>
            </div>

            <div class="assigned-info" style="font-size:12px;color:#555;">
                Assigned: <?= $assigned ? $assigned : "None" ?>
                &nbsp; <?= $isLockedIcon ?>
            </div>
        </div>

        <!-- Action Buttons (Assign / Unassign / Lock) -->
        <div class="client-icons">

            <?php if (!$assigned): ?>
                <!-- ASSIGN BUTTON -->
                <button class="assign-btn" 
                        data-id="<?= $id ?>" 
                        title="Assign to me">
                    ➕
                </button>

            <?php elseif ($isMine): ?>
                <!-- UNASSIGN BUTTON -->
                <button class="unassign-btn" 
                        data-id="<?= $id ?>" 
                        title="Unassign from me">
                    ➖
                </button>

            <?php else: ?>
                <!-- REQUEST TRANSFER -->
                <button class="request-transfer-btn"
                        data-id="<?= $id ?>"
                        data-current="<?= $assigned ?>"
                        title="Request transfer">
                    🔄
                </button>
            <?php endif; ?>

        </div>
    </div>

<?php endforeach; ?>
