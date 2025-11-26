<?php
session_start();
include "../db_connect.php";

$id = intval($_GET["client_id"]);

$stmt = $conn->prepare("SELECT * FROM chat WHERE client_id=:id ORDER BY id ASC");
$stmt->execute([":id"=>$id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
