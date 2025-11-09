<?php
include 'db_connect.php';

// secure token to stop abuse
$token = $_GET['token'] ?? '';

// compare with token stored in environment
$expected = getenv("REMINDER_TOKEN");

if (!$expected || $token !== $expected) {
  http_response_code(403);
  echo "Unauthorized";
  exit();
}

echo "Reminder script running...\n";

// Get users whose due_date is 7 days or 3 days away
$sql = "
SELECT email, full_name, due_date
FROM users
WHERE due_date = CURRENT_DATE + INTERVAL '7 days'
   OR due_date = CURRENT_DATE + INTERVAL '3 days'
";

$stmt = $conn->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $email = $row['email'];
    $name  = $row['full_name'];
    $due   = $row['due_date'];

    // include PHPMailer
    require 'mail.php';

    sendReminderMail($email, $name, $due);
}

echo "Done!";
