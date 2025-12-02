<?php
require_once "../../db_connect.php";

$msgID = $_POST["id"] ?? 0;
if (!$msgID) exit;

// get grouped reactions
$stmt = $conn->prepare("
    SELECT emoji, COUNT(*) AS total
    FROM chat_reactions
    WHERE chat_id = ?
    GROUP BY emoji
    ORDER BY total DESC
");
$stmt->execute([$msgID]);
$reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$reactions) exit;

// convert to stacked bar like Messenger
echo "<div class='reaction-bar'>";
foreach ($reactions as $r) {
    echo "<span class='reaction-item'>{$r['emoji']}</span>";
}
echo "</div>";
