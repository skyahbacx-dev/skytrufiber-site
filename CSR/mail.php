<?php
// mail.php
// Simple PHPMailer wrapper. Requires composer autoload and phpmailer/phpmailer installed.
//
// Put SMTP credentials in environment variables:
//   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, MAIL_FROM, MAIL_FROM_NAME

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php'; // adjust path to composer autoload

function send_mail($to_email, $to_name, $subject, $body_html, $body_text = '') {
    $mail = new PHPMailer(true);
    try {
        // SMTP config from env
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER') ?: 'billing@yourdomain.com';
        $mail->Password = getenv('SMTP_PASS') ?: 'password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT') ?: 587;

        $from = getenv('MAIL_FROM') ?: 'billing@skytrufiber.com';
        $from_name = getenv('MAIL_FROM_NAME') ?: 'SkyTruFiber Billing';

        $mail->setFrom($from, $from_name);
        $mail->addReplyTo($from, $from_name);

        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body_html;
        if ($body_text) $mail->AltBody = $body_text;

        $mail->send();
        return ['ok' => true];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}
