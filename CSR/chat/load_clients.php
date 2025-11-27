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
            u.id, u.full_name, u.email, u.is_online,
            COALESCE(
                (SELECT message FROM chat WHERE client_id = u.id ORDER BY created_at DESC LIMIT 1),
                ''
            ) AS last_message,
            COALESCE(
                (SELECT seen FROM chat_read WHERE client_id = u.id AND csr = :csr LIMIT 1),
                1
            ) AS seen_flag
        FROM users u
        ORDER BY u.full_name ASC
    ");
    $stmt->execute([":csr" => $csr]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $html = "";

    if (!$users) {
        echo "<p style='padding:10px;color:#777'>No clients available.</p>";
        exit;
    }

    foreach ($users as $u) {
        $online = $u["is_online"] ? "online" : "offline";
        $seenIcon = $u["seen_flag"] == 1 ? "✓✓" : "✓"; 

        $html .= "
        <div class='client-item' data-id='{$u['id']}'>
            <div class='client-status $online'></div>
            <div class='client-info'>
                <strong>{$u['full_name']}</strong>
                <small>{$u['email']}</small>
                <small class='last-msg'>{$u['last_message']}</small>
            </div>

            <div class='client-icons'>
                <button class='icon add-client' data-id='{$u['id']}'><i class='fa fa-plus'></i></button>
                <button class='icon remove-client' data-id='{$u['id']}'><i class='fa fa-minus'></i></button>
                <button class='icon lock-client' data-id='{$u['id']}'><i class='fa fa-lock'></i></button>
            </div>
        </div>";
    }

    echo $html;

} catch (PDOException $e) {
    echo "DB ERROR: " . $e->getMessage();
}
