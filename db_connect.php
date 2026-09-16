<?php
/* ============================================================
   DATABASE CONNECTION (Neon PostgreSQL)
   ------------------------------------------------------------
   Credentials are read from environment variables that are set
   in the Render dashboard (Settings -> Environment). The password
   is NEVER written in this file.

   Required environment variables:
     PGHOST      e.g. ep-xxxx-pooler.c-2.us-west-2.aws.neon.tech
     PGDATABASE  e.g. neondb
     PGUSER      e.g. neondb_owner
     PGPASSWORD  (secret)
     PGPORT      optional, defaults to 5432
============================================================ */

$host     = getenv("PGHOST");
$port     = getenv("PGPORT") ?: "5432";
$dbname   = getenv("PGDATABASE");
$user     = getenv("PGUSER");
$password = getenv("PGPASSWORD");

try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Log the real reason for admins, but never show DB details to visitors.
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(503);
    die("Service temporarily unavailable. Please try again in a moment.");
}
?>
