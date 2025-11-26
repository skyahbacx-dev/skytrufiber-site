<?php
session_start();
include "../db_connect.php";
$csr = $_SESSION["csr_user"];
$id = $_POST["client_id"];
$conn->prepare("UPDATE users SET assigned_csr=:c WHERE id=:id")->execute([":c"=>$csr,":id"=>$id]);
