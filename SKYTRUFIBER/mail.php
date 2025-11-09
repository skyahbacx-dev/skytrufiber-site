<?php
// ===============================
// SkyTruFiber — Email Reminder Sender
// Using PHPMailer + ENV variables
// ===============================

require __DIR__ . '/../vendor/autoload.php'; // PHPMailer from composer
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ==========================================
// LOAD ENV VARIABLES (Render automatically sets)
// ==========================================

$SMTP_HOST     = getenv("SMTP_HOST");
$SMTP_PORT     = getenv("SMTP_PORT");
$SMTP_USER     = getenv("SMTP_USER");
$SMTP_PASSWORD = getenv("SMTP_PASSWORD");
$SMTP_FROM     = getenv("SMTP_FROM") ?: "no-reply@skytrufiber.com";
$SMTP_FROMNAME = getenv("SMTP_FROMNAME") ?: "SkyTruFiber Billing";

// Check if env is missing
if (!$SMTP_HOST || !$SMTP_PORT || !$SMTP_USER || !$SMTP_PASSWORD) {
    echo "Missing SMTP environment variables.";
    exit;
}

// =========================================================
// FETCH ALL REMINDERS THAT NEED TO BE SENT
// =========================================================
// status = 'pending' means scheduled but not yet sent
// reminder_type = 1_WEEK or 3_DAYS

$sql = "
SELECT 
    r.id AS reminder_id,
    r.client_id,
    r.reminder_type,
    c.name AS client_name,
    c.email AS client_email,
    c.due_date
FROM reminders r
LEFT JOIN clients c ON c.id = r.client_id
WHERE r.status = 'pending'
ORDER BY r.id ASC
";

$stmt = $conn->query($sql);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$reminders) {
    echo "No pending reminders.\n";
    exit;
}

// =========================================================
// HELPER FUNCTION — SEND EMAIL
// =========================================================
function sendReminder($toEmail, $toName, $messageBody, $subject)
{
    global $SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASSWORD, $SMTP_FROM, $SMTP_FROMNAME;

    $mail = new PHPMailer(true);

    try {
        // SMTP config
        $mail->isSMTP();
        $mail->Host = $SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $SMTP_PORT;

        // From
        $mail->setFrom($SMTP_FROM, $SMTP_FROMNAME);

        // To
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($messageBody);

        $mail->send();
        return true;

    } catch (Exception $e) {
        echo "Email Error: " . $mail->ErrorInfo . "\n";
        return false;
    }
}

// =========================================================
// PROCESS REMINDERS
// =========================================================
foreach ($reminders as $rem) {
    
    $clientEmail = $rem['client_email'];
    $clientName  = $rem['client_name'];
    $dueDate     = $rem['due_date'];
    $type        = $rem['reminder_type'];
    $reminderId  = $rem['reminder_id'];

    // Skip invalid email
    if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        echo "Skipping invalid email for client $clientName\n";
        continue;
    }

    // Create proper message
    $subject = "Billing Reminder — SkyTruFiber";

    if ($type == "1_WEEK") {
        $message = "
Hello $clientName,<br><br>
This is a friendly reminder that your SkyTruFiber bill is due on <b>$dueDate</b>.
You are receiving this notice 1 week before the due date.<br><br>
Thank you for choosing SkyTruFiber!
        ";
    } else {
        $message = "
Hello $clientName,<br><br>
This is your SkyTruFiber billing reminder.<br>
Your payment is due on <b>$dueDate</b> — this reminder is being sent 3 days before your disconnection date.<br><br>
Please settle your due to avoid interruption.<br>
Thank you!
        ";
    }

    // SEND
    $sent = sendReminder($clientEmail, $clientName, $message, $subject);

    if ($sent) {
        // Update reminder status
        $update = $conn->prepare("UPDATE reminders SET status = 'sent', sent_at = NOW() WHERE id = :id");
        $update->execute([':id' => $reminderId]);

        echo "✅ Reminder sent to $clientName ($clientEmail)\n";
    } else {
        echo "❌ Failed to send reminder to $clientName\n";
    }
}

echo "=== Reminder processing complete ===\n";
?>
