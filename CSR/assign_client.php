<?php
session_start();
include '../db_connect.php';

$conn->prepare("UPDATE users SET assigned_csr=? WHERE id=?")
    ->execute([$_SESSION["csr_user"], $_POST["client_id"]]);
