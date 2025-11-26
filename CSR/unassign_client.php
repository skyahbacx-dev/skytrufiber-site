<?php
include '../db_connect.php';

$conn->prepare("UPDATE users SET assigned_csr=NULL WHERE id=?")
    ->execute([$_POST["client_id"]]);
