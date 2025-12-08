<?php
session_start();
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!empty($email)) {
        try {
            // Check user
            $stmt = $conn->prepare("SELECT full_name, email, account_number FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Send email using Gmail SMTP
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'skytrufiberbilling@gmail.com'; // <-- your Gmail
                    $mail->Password = 'hmmt suww lpyt oheo';    // <-- 16-digit Google app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('skytrufiberbilling@gmail.com', 'SkyTruFiber Support');
                    $mail->addAddress($user['email'], $user['full_name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Your SkyTruFiber Account Number';
                    $mail->Body = "
                        Hello <b>{$user['full_name']}</b>,<br><br>
                        Your account number is:<br>
                        <h2>{$user['account_number']}</h2>
                        This serves as your login password.<br><br>
                        Regards,<br>
                        SkyTruFiber Support Team
                    ";

                    $mail->send();
                    header("Location: skytrufiber.php?msg=success");
                    exit;

                } catch (Exception $e) {
                    $message = "Failed to send email. Error: " . $mail->ErrorInfo;
                }

            } else {
                $message = "No user found with that email.";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "Please enter your email.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>
    <h2>Retrieve Account Number</h2>

    <?php if ($message): ?>
        <p style="color:red;"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <button type="submit">Send Account Number</button>
    </form>
</body>
</html>
