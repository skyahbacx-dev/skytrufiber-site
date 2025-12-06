<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) exit("No client selected.");

$csrUser = $_SESSION["csr_user"];

$stmt = $conn->prepare("
    SELECT 
        id,
        full_name,
        email,
        district,
        barangay,
        is_online,
        assigned_csr,
        is_locked
    FROM users
    WHERE id = :cid
    LIMIT 1
");
$stmt->execute([":cid" => $clientID]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) exit("Client not found.");

$isAssignedToMe = ($c["assigned_csr"] === $csrUser) ? "yes" : "no";
$isLocked       = $c["is_locked"] ? "true" : "false";

echo "
    <p><strong>Name:</strong> {$c['full_name']}</p>
    <p><strong>Email:</strong> {$c['email']}</p>
    <p><strong>District:</strong> {$c['district']}</p>
    <p><strong>Barangay:</strong> {$c['barangay']}</p>
    <p><strong>Status:</strong> " . ($c["is_online"] ? "Online" : "Offline") . "</p>
    <p><strong>Lock State:</strong> " . ($c["is_locked"] ? "Locked" : "Unlocked") . "</p>

    <!-- 🔥 HIDDEN META REQUIRED BY JS -->
    <div id='client-meta'
        data-assigned='$isAssignedToMe'
        data-locked='$isLocked'>
    </div>
";
