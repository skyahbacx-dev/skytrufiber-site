<?php
$servername = getenv('DB_HOST') ?: 'localhost';
$username   = getenv('DB_USER') ?: 'root';
$password   = getenv('DB_PASS') ?: '';
$database   = getenv('DB_NAME') ?: 'skytrufiber_db';

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>
