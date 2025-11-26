<?php
session_start();
include "../db_connect.php";
$id = $_POST["client_id"];
$conn->prepare("UPDATE users SET assigned_csr=NULL WHERE id=:id")->execute([":id"=>$id]);
