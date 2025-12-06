<?php
include __DIR__ . "/../db_connect.php";

/* -----------------------------
   CRON SECURITY TOKEN CHECK
------------------------------ */

// Secret token – change this to your own!
$CRON_TOKEN = "AE92JF83HF82HSLA29FD";

if (!isset($_GET['token']) || $_GET['token'] !== $CRON_TOKEN) {
    http_response_code(403);
    exit("⛔ Access Denied.");
}

// Optional: disable browser execution entirely
if (php_sapi_name() !== 'cli' && !isset($_GET['token'])) {
    http_response_code(403);
    exit("⛔ Forbidden.");
}


/* Get all subscribers with installation date */
$users = $conn->query("
    SELECT id, full_name, email, account_number, date_installed
    FROM users
    WHERE date_installed IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {

    $installDate = new DateTime($u['date_installed']);
    $today = new DateTime();

    // Calculate how many months have passed
    $monthsPassed = $installDate->diff($today)->m + ($installDate->diff($today)->y * 12);

    // Calculate next due date
    $dueDate = (clone $installDate)->modify("+{$monthsPassed} month");
    if ($dueDate < $today) {
        $dueDate = (clone $installDate)->modify("+".($monthsPassed + 1)." month");
    }

    $diffDays = (int)$today->diff($dueDate)->format("%r%a");

    $reminderType = null;
    if ($diffDays === 7) $reminderType = "7_days";
    elseif ($diffDays === 3) $reminderType = "3_days";
    elseif ($diffDays === 1) $reminderType = "1_day";
    else continue;

    // Prevent duplicates for SAME due date
    $stmt = $conn->prepare("
        SELECT id FROM reminder_logs
        WHERE user_id = :uid AND reminder_type = :type AND due_date = :due
    ");
    $stmt->execute([
        ':uid' => $u['id'],
        ':type' => $reminderType,
        ':due' => $dueDate->format("Y-m-d")
    ]);

    if ($stmt->fetch()) continue;

    /* Email content */
    $subject = "Billing Reminder – Payment Due Soon";
    $message = "
        Hello {$u['full_name']},<br><br>
        This is a reminder that your next bill is due on:
        <h3>{$dueDate->format('Y-m-d')}</h3>
        Please settle your account to avoid service interruption.<br><br>
        Thank you!
    ";

    $headers = "Content-Type: text/html; charset=UTF-8";

    $sent = mail($u['email'], $subject, $message, $headers);
    $status = $sent ? "sent" : "failed";

    /* Log it */
    $log = $conn->prepare("
        INSERT INTO reminder_logs (user_id, account_number, email, reminder_type, due_date, status)
        VALUES (:uid, :acc, :email, :type, :due, :status)
    ");
    $log->execute([
        ':uid' => $u['id'],
        ':acc' => $u['account_number'],
        ':email' => $u['email'],
        ':type' => $reminderType,
        ':due' => $dueDate->format("Y-m-d"),
        ':status' => $status
    ]);
}
