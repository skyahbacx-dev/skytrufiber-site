<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";  // adjust if your path differs

$csr = $_POST['csr'] ?? null;

if (!$csr) {
    echo "No CSR specified.";
    exit;
}

// Fetch assigned clients for this CSR
$query = $conn->prepare("
    SELECT u.id, u.full_name, u.is_online,
        (SELECT message FROM chat 
            WHERE client_id = u.id 
            ORDER BY created_at DESC LIMIT 1) AS last_message,
        (SELECT sender_type FROM chat 
            WHERE client_id = u.id 
            ORDER BY created_at DESC LIMIT 1) AS last_sender,
        (SELECT seen FROM chat 
            WHERE client_id = u.id 
            ORDER BY created_at DESC LIMIT 1) AS last_seen
    FROM users u
    WHERE u.assigned_csr = ?
    ORDER BY u.full_name ASC
");
$query->execute([$csr]);
$clients = $query->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "<div class='no-clients'>No assigned clients</div>";
    exit;
}

foreach ($clients as $client) {

    $statusClass = $client['is_online'] ? "online" : "offline";

    // format last message preview
    $preview = $client['last_message'] ?? "No messages yet";
    if (strlen($preview) > 25) {
        $preview = substr($preview, 0, 25) . "...";
    }

    // Show unread badge if last message was from client and not seen
    $unread = ($client['last_sender'] === "client" && $client['last_seen'] == 0)
        ? "<span class='unread-dot'></span>"
        : "";

    echo "
    <div class='client-item' data-id='{$client['id']}' data-name='{$client['full_name']}' data-status='{$statusClass}'>
        <div class='client-avatar'>
            <img src='../../default.png'>
        </div>
        <div class='client-info'>
            <div class='client-name-row'>
                <span class='client-name'>{$client['full_name']}</span>
                {$unread}
            </div>
            <div class='client-preview'>
                {$preview}
            </div>
        </div>
        <div class='client-status-dot {$statusClass}'></div>
    </div>";
}
