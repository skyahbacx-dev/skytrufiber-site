<?php
include "../db_connect.php";

$client_id = (int)($_POST["client_id"] ?? 0);
$typing    = (int)($_POST["typing"] ?? 0);

$conn->prepare("
    INSERT INTO typing_status (client_id, typing)
    VALUES (:cid, :t)
    ON CONFLICT (client_id)
    DO UPDATE SET typing = :t
")->execute([
    ":cid" => $client_id,
    ":t"   => $typing
]);

echo "ok";
?>
