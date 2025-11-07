<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "skytrufiber_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("DB connection failed: " . $conn->connect_error); }
?>
