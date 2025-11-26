<?php
session_start();
include "../db_connect.php";

$msg = trim($_POST["message"]);
$id = intval($_POST["client_id"]);

$conn->prepare("
INSERT INTO chat (client_id, sender_type, message, created_at, seen)
VALUES (:id,'csr',:msg,NOW(),false)
")->execute([":id"=>$id,":msg"=>$msg]);

echo "OK";
