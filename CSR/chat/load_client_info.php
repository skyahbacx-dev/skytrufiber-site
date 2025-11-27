<?php
require "../../db_connect.php";

$id = $_POST["id"];

$stmt = $conn->prepare("SELECT full_name, account_number, email, district, barangay, is_online 
                        FROM users WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

echo "
<h3>".htmlspecialchars($client['full_name'])."</h3>

<div class='client-action-icons'>
    <span onclick='assignClient()' class='icon-btn add'>➕</span>
    <span onclick='unassignClient()' class='icon-btn minus'>➖</span>
    <span onclick='lockClient()' class='icon-btn lock'>🔒</span>
</div>


<p><strong>Account #:</strong> {$client['account_number']}</p>
<p><strong>Email:</strong> {$client['email']}</p>
<p><strong>District:</strong> {$client['district']}</p>
<p><strong>Barangay:</strong> {$client['barangay']}</p>
<p><strong>Status:</strong> ".($client["is_online"] ? "🟢 Online" : "🔴 Offline")."</p>
";
