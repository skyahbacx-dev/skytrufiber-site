<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
if (!$csr) {
    http_response_code(403);
    exit("Unauthorized");
}

try {

    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.full_name,
            u.email,
            u.is_online,
            u.assigned_csr,
            COALESCE(
                (SELECT message FROM chat WHERE client_id = u.id ORDER BY created_at DESC LIMIT 1),
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

        $online = $u["is_online"] ? "online" : "offline";

        $isUnassigned = empty($u["assigned_csr"]);
        $isMine = ($u["assigned_csr"] === $csr);
        $isLocked = (!$isUnassigned && !$isMine);

        $addBtn = $isUnassigned
            ? "<button class='icon add-client' data-id='{$u['id']}'><i class='fa fa-plus'></i></button>" : "";

        $removeBtn = $isMine
            ? "<button class='icon remove-client' data-id='{$u['id']}'><i class='fa fa-minus'></i></button>" : "";

        $lockBtn = $isLocked
            ? "<button class='icon lock-client' disabled><i class='fa fa-lock'></i></button>" : "";

        echo "
        <div class='client-item' data-id='{$u['id']}'>
            <div class='client-status $online'></div>
            <div class='client-info'>
                <strong>{$u['full_name']}</strong>
                <small>{$u['email']}</small>
                <small class='last-msg'>{$u['last_message']}</small>
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
    echo \"DB ERROR: \" . $e->getMessage();
}
