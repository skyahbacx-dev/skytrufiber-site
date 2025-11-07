<?php
$host = "ep-wandering-recipe-afc37uqg-pooler.c-2.us-west-2.aws.neon.tech";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_T70gIMvUcxtk"; // replace this with your real password

try {
    $conn = new PDO("pgsql:host=$host;dbname=$dbname;port=5432;sslmode=require", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "✅ Database connection successful!";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
    exit;
}
?>
