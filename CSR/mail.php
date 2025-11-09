<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php'; // Adjust path if needed

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = getenv('MAIL_HOST');     // smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USERNAME'); // skytrufiberbilling@gmail.com
        $mail->Password   = getenv('MAIL_PASSWORD'); // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('MAIL_PORT') ?: 587;

        $mail->setFrom(getenv('MAIL_FROM'), 'SkyTruFiber Billing');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
