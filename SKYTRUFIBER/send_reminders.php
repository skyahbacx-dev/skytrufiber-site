<?php
include 'db_connect.php';

// secure token to stop abuse
$token = $_GET['token'] ?? '';
if ($token !== 'MY_SECRET_TOKEN_123') {
    http_response_code(403);
    exit("Unauthorized");
}

echo "Reminder script running...\n";

// Your reminder logic here...
// Example query:
$stmt = $conn->query("
    SELECT u.email, u.full_name, u.due_date
    FROM users u
    WHERE u.due_date <= CURRENT_DATE + INTERVAL '7 days'
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Send email using PHPMailer or your mail.php
    // ...
}

echo "Done!";
