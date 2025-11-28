<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    echo "Client ID missing.";
    exit;
}

try {
    $sql = "
        SELECT 
            u.id,
            u.full_name,
            u.email,
            u.district,
            u.barangay,
            u.is_online,
            u.assigned_csr,
            u.is_locked
        FROM users u
        WHERE u.id = :cid
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":cid", $client_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Client not found.";
        exit;
    }

    echo "
        <div class='client-info-section'>
            <p><strong>Name:</strong> " . htmlspecialchars($user['full_name']) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>
            <p><strong>District:</strong> " . htmlspecialchars($user['district']) . "</p>
            <p><strong>Barangay:</strong> " . htmlspecialchars($user['barangay']) . "</p>
            <p><strong>Status:</strong> " . ($user['is_online'] ? "Online" : "Offline") . "</p>
            <p><strong>Lock State:</strong> " . ($user['is_locked'] ? "Locked" : "Unlocked") . "</p>
        </div>
    ";

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
