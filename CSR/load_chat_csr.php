<?php
include "../db_connect.php";

$client_id = $_POST["client_id"] ?? 0;

$q = $conn->prepare("SELECT * FROM chat WHERE user_id = ? ORDER BY created_at ASC");
$q->execute([$client_id]);

$messages = $q->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $m) {
    $side = ($m["sender_type"] === "csr") ? "me" : "them";

    echo "<div class='message $side'>" . nl2br(htmlspecialchars($m["message"])) . "</div>";
}
?>