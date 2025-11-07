<?php
$servername = "sql106.infinityfree.com";  
$username   = "if0_40346931";            
$password   = "AHBA1234567890";         
$database   = "if0_40346931_skytrufiber_db";   

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>
