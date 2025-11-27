<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

$query = $conn->prepare("
    SELECT * FROM chat 
    WHERE client_id = ?
    ORDER BY created_at ASC
");
$query->execute([$client_id]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $bubble = ($row["sender_type"] === "CSR") ? "csr-msg" : "client-msg";

    echo "<div class='msg $bubble'>";
    echo nl2br(htmlspecialchars($row["message"]));

    echo "<div class='timestamp'>" . date("h:i A", strtotime($row["created_at"])) . "</div>";
    echo "</div>";
}
