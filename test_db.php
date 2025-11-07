<?php
echo "🔍 Testing database connection...<br>";

$host = "ep-wandering-recipe-afc37uqg-pooler.c-2.us-west-2.aws.neon.tech";
$port = "5432";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_KpGd1ogr8qhM";

try {
  $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $password);
  echo "✅ Connected successfully to Neon!";
} catch (PDOException $e) {
  echo "❌ Connection failed: " . $e->getMessage();
}
?>
