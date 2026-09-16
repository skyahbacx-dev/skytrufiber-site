<?php
include __DIR__ . "/../db_connect.php";

/* --------------------------------------
   🔐 SECURITY TOKEN PROTECTION
--------------------------------------- */
$CRON_TOKEN = "AE92JF83HF82HSLA29FD"; // <-- CHANGE this for security

if (!isset($_GET['token']) || $_GET['token'] !== $CRON_TOKEN) {
    http_response_code(403);
    exit("⛔ Unauthorized.");
}

/* --------------------------------------
   📅 GET TODAY
--------------------------------------- */
$today = new DateTime();

/* --------------------------------------
   📌 FETCH ALL USERS WITH BILLING DATES
--------------------------------------- */
$stmt = $conn->prepare("
    SELECT id, full_name, email, account_number, date_installed
    FROM users
    WHERE date_installed IS NOT NULL
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------
   🔁 PROCESS EACH USER
--------------------------------------- */
foreach ($users as $u) {

    $installDate = new DateTime($u['date_installed']);

    // Calculate how many months since installation
    $diff = $installDate->diff($today);
    $monthsPassed = ($diff->y * 12) + $diff->m;

    // Their next due date
    $dueDate = (clone $installDate)->modify("+{$monthsPassed} month");

    // If the due date already passed, move to next month
    if ($dueDate < $today) {
        $dueDate = (clone $installDate)->modify("+".($monthsPassed + 1)." month");
    }

    // Days remaining until due date
    $daysRemaining = (int)$today->diff($dueDate)->format("%r%a");

    /* -------------------------
       🎯 Determine Reminder Type
    -------------------------- */
    if ($daysRemaining === 7) {
        $type = "7_days_before";
    } elseif ($daysRemaining === 3) {
        $type = "3_days_before";
    } elseif ($daysRemaining === 1) {
        $type = "1_day_before";
    } else {
        continue;
    }

    /* -------------------------
       🔄 Check if already sent
    -------------------------- */
    $check = $conn->prepare("
        SELECT 1 FROM reminder_logs
        WHERE user_id = :uid AND reminder_type = :type AND due_date = :due
    ");
    $check->execute([
        ':uid' => $u['id'],
        ':type' => $type,
        ':due' => $dueDate->format("Y-m-d")
    ]);

    if ($check->fetch()) {
        continue; // Already sent → skip
    }

    /* -------------------------
       ✉ Generate email content
    -------------------------- */
    $emailBody = buildReminderEmail(
        $u['full_name'],
        $u['account_number'],
        $dueDate->format("Y-m-d"),
        $type
    );

    $subjectMap = [
        "7_days_before" => "Your SkyTruFiber Billing is Due in 7 Days",
        "3_days_before" => "Your SkyTruFiber Billing is Due in 3 Days",
        "1_day_before" => "Your SkyTruFiber Billing is Due Tomorrow"
    ];

    $subject = $subjectMap[$type];

    /* -------------------------
       📧 Send Email
    -------------------------- */
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: SkyTruFiber Support <no-reply@ahbadevt.com>\r\n";

    $sent = mail($u['email'], $subject, $emailBody, $headers);

    /* -------------------------
       📝 Log reminder
    -------------------------- */
    $log = $conn->prepare("
        INSERT INTO reminder_logs (user_id, reminder_type, email, due_date, message, status)
        VALUES (:uid, :type, :email, :due, :msg, :status)
        ON CONFLICT (user_id, reminder_type, due_date) DO NOTHING
    ");

    $log->execute([
        ':uid' => $u['id'],
        ':type' => $type,
        ':email' => $u['email'],
        ':due' => $dueDate->format("Y-m-d"),
        ':msg' => $emailBody,
        ':status' => $sent ? "sent" : "failed"
    ]);
}

echo "Reminder cron completed.";
exit;

/* =====================================================
   📧 EMAIL TEMPLATE FUNCTION
===================================================== */
function buildReminderEmail($name, $account, $dueDate, $type) {

    $messages = [
        "7_days_before" => "
            Hello $name,<br><br>
            This is a friendly reminder that your SkyTruFiber subscription is due in <b>7 days</b>.<br><br>
            <b>Account Number:</b> $account<br>
            <b>Due Date:</b> $dueDate<br><br>
            Please settle your payment before the due date to avoid service interruption.<br><br>
            Thank you,<br>
            SkyTruFiber Support
        ",

        "3_days_before" => "
            Hello $name,<br><br>
            Your SkyTruFiber subscription is due in <b>3 days</b>.<br><br>
            <b>Account Number:</b> $account<br>
            <b>Due Date:</b> $dueDate<br><br>
            Kindly settle your balance soon to avoid disconnection.<br><br>
            Thank you,<br>
            SkyTruFiber Support
        ",

        "1_day_before" => "
            Hello $name,<br><br>
            This is a reminder that your SkyTruFiber subscription is due <b>tomorrow</b>.<br><br>
            <b>Account Number:</b> $account<br>
            <b>Due Date:</b> $dueDate<br><br>
            Please make your payment to ensure uninterrupted service.<br><br>
            Thank you,<br>
            SkyTruFiber Support
        "
    ];

    return $messages[$type];
}
