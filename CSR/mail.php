<?php
require_once __DIR__ . "/../vendor/autoload.php"; // PHPMailer autoload
include "../db_connect.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load secrets from Render environment
$SMTP_HOST      = getenv("SMTP_HOST");
$SMTP_PORT      = getenv("SMTP_PORT");
$SMTP_USER      = getenv("SMTP_USER");
$SMTP_PASS      = getenv("SMTP_PASS");
$SMTP_FROM      = getenv("SMTP_FROM");
$SMTP_FROM_NAME = getenv("SMTP_FROM_NAME");

function sendReminderEmail($toEmail, $toName, $reminderType) {
    global $SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS, $SMTP_FROM, $SMTP_FROM_NAME;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = $SMTP_USER;
        $mail->Password   = $SMTP_PASS;
        $mail->SMTPSecure = "tls";
        $mail->Port       = $SMTP_PORT;

        // Recipients
        $mail->setFrom($SMTP_FROM, $SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);

        // Message templates
        if ($reminderType === "1_WEEK") {
            $subject = "📅 SkyTruFiber Reminder — Upcoming Due Date (1 Week Left)";
            $body    = "
                <h2>Hi $toName,</h2>
                <p>This is a friendly reminder from <strong>SkyTruFiber</strong>.</p>
                <p>Your payment is due in <strong>1 week</strong>.</p>
                <p>Please settle to avoid interruption of service.</p>
                <p>Thank you,<br>SkyTruFiber Billing Team</p>
            ";
        }
        else if ($reminderType === "3_DAYS") {
            $subject = "⚠️ SkyTruFiber Disconnection Notice — 3 Days Before Due";
            $body    = "
                <h2>Dear $toName,</h2>
                <p>Your account is scheduled for disconnection in <strong>3 days</strong>.</p>
                <p>Please make your payment immediately to avoid service interruption.</p>
                <p>SkyTruFiber Billing</p>
            ";
        }
        else {
            throw new Exception("Invalid reminder type.");
        }

        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Send email
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>
