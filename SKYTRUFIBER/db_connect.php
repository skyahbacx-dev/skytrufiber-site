<?php
$host = "ep-wandering-recipe-afc37uqg-pooler.c-2.us-west-2.aws.neon.tech";
$port = "5432";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_KpGd1ogr8qhM";

try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
