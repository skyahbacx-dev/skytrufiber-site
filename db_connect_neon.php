<?php
try {
    $conn = new PDO(
        "pgsql:host=" . getenv("PGHOST") .
        ";port=5432;dbname=" . getenv("PGDATABASE") .
        ";sslmode=require",
        getenv("PGUSER"),
        getenv("PGPASSWORD"),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Neon connection failed: " . $e->getMessage());
}
