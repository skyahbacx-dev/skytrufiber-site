<?php
$host = "$host = "ep-wandering-recipe-afc37ugq.us-west-2.aws.neon.tech";";
$port = "5432";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_GsU27iMDxudX"; // <-- your current Neon password

try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "✅ Connected successfully to Neon PostgreSQL!";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
