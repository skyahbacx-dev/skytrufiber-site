<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
if (!$csr) {
    http_response_code(403);
    exit("Unauthorized");
}

try {
    // Get all clients; we use only columns that really exist
    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.is_online,
            u.assigned_csr,
            COALESCE(
                (SELECT message
                 FROM chat
                 WHERE client_id = u.id
                 ORDER BY created_at DESC
                 LIMIT 1),
                ''
            ) AS last_message
        FROM users u
        ORDER BY u.full_name ASC
    ");
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$users) {
        echo "<p style='padding:10px;color:#777'>No clients registered.</p>";
        exit;
    }

    foreach ($users as $u) {
        $id    = (int)$u["id"];
        $name  = htmlspecialchars($u["full_name"]);
        $email = htmlspecialchars($u["email"]);
        $last  = htmlspecialchars($u["last_message"]);

        $onlineClass = $u["is_online"] ? "online" : "offline";

        // assignment logic
        $isUnassigned = empty($u["assigned_csr"]);
        $isMine       = ($u["assigned_csr"] === $csr);
        $isLocked     = (!$isUnassigned && !$isMine);

        $addBtn    = $isUnassigned ? "<button class='client-action-btn add-client' data-id='$id'><i class='fa fa-plus'></i></button>" : "";
        $removeBtn = $isMine       ? "<button class='client-action-btn remove-client' data-id='$id'><i class='fa fa-minus'></i></button>" : "";
        $lockBtn   = $isLocked     ? "<button class='client-action-btn lock-client' disabled><i class='fa fa-lock'></i></button>" : "";

        echo "
        <div class='client-item' data-id='$id' data-name='$name'>
            <div class='client-status $onlineClass'></div>
            <div class='client-row-left'>
                <strong>$name</strong>
                <small>$email</small>
                <small class='last-msg'>$last</small>
            </div>
            <div class='client-row-actions'>
                $addBtn
                $removeBtn
                $lockBtn
            </div>
        </div>";
    }

} catch (PDOException $e) {
    echo 'DB ERROR: ' . htmlspecialchars($e->getMessage());
}
