<?php
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=skytrufiber_db;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Local MySQL connection failed: " . $e->getMessage());
}
