<?php
session_start();
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



$message = '';
$forgotMessage = '';

// FORGOT PASSWORD POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_email'])) {
    $email = trim($_POST['forgot_email']);

    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $forgotMessage = "Invalid email format.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT full_name, email, account_number FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'skytrufiberbilling@gmail.com';
                        $mail->Password = 'hmmt suww lpyt oheo';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('skytrufiberbilling@gmail.com', 'SkyTruFiber Support');
                        $mail->addAddress($user['email'], $user['full_name']);

                        $mail->isHTML(true);
                        $mail->Subject = 'Your SkyTruFiber Account Number';
                        $mail->Body = "
                            <p>Hello <b>{$user['full_name']}</b>,</p>
                            <p>Your account number is: <strong>{$user['account_number']}</strong></p>
                            <p>This serves as your login password.</p>
                            <p>Regards,<br>SkyTruFiber Support Team</p>
                        ";

                        $mail->send();
                        $forgotMessage = "Email sent successfully!";
                    } catch (Exception $e) {
                        $forgotMessage = "Failed to send email. Error: " . $mail->ErrorInfo;
                    }
                } else {
                    $forgotMessage = "No user found with that email.";
                }
            } catch (PDOException $e) {
                $forgotMessage = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $forgotMessage = "Please enter your email.";
    }
}

// NORMAL LOGIN POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name']) && isset($_POST['password'])) {
    $email_or_name = trim($_POST['full_name']);
    $password = $_POST['password'] ?? '';
    $concern = trim($_POST['concern'] ?? '');

    if ($email_or_name && $password) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :input OR full_name = :input LIMIT 1");
            $stmt->execute([':input' => $email_or_name]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $updateTicket = $conn->prepare("UPDATE users SET ticket_status = 'unresolved' WHERE id = :cid");
                $updateTicket->execute([':cid' => $user['id']]);

                $_SESSION['client_id']   = $user['id'];
                $_SESSION['client_name'] = $user['full_name'];
                $_SESSION['email']       = $user['email'];

                if (!empty($concern)) {
                    $insert = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at) VALUES (:cid, 'client', :msg, false, false, NOW())");
                    $insert->execute([':cid' => $user['id'], ':msg' => $concern]);
                }

                header("Location: chat/chat_support.php?username=" . urlencode($user['full_name']));
                exit;
            } else {
                $message = "❌ Invalid email/full name or password.";
            }
        } catch (PDOException $e) {
            $message = "⚠ Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "⚠ Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber - Customer Portal</title>
<style>
body {
  font-family:"Segoe UI", Arial, sans-serif;
  background:linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0;
}

form {
  background:rgba(255,255,255,0.45);
  padding:25px; border-radius:20px; width:380px;
  backdrop-filter:blur(12px); box-shadow:0 8px 25px rgba(0,0,0,0.15);
}

input, textarea { width:100%; padding:10px; margin-top:5px; border-radius:10px; border:1px solid #ccc; box-sizing:border-box; }
textarea { height:80px; resize:none; }

button { width:100%; padding:12px; background:#00a6b6; color:white; border:none; border-radius:50px; cursor:pointer; font-weight:bold; font-size:16px; margin-top:15px; }
button:hover { background:#008c96; transform:translateY(-2px); }
button:active { transform:scale(.97); }

.forgot-form { max-height:0; overflow:hidden; transition:max-height 0.5s ease, padding 0.5s ease; }
.forgot-form.active { max-height:180px; padding-top:10px; }

.message { color:red; font-size:0.9em; margin-bottom:10px; }
.success { color:green; }

a { font-size:0.9em; text-decoration:none; color:#0077a3; }
a:hover { text-decoration:underline; }

.logo { width:150px; border-radius:50%; display:block; margin:0 auto 15px; }
</style>
</head>
<body>

<div>
<img src="../SKYTRUFIBER.png" class="logo">

<form method="POST">
  <h2 style="text-align:center; color:#004466;">Customer Service Portal</h2>

  <label>Email or Full Name:</label>
  <input type="text" name="full_name" required>

  <label>Password:</label>
  <input type="password" name="password" required>

  <label>Concern / Inquiry:</label>
  <textarea name="concern" required></textarea>

  <button type="submit">Submit</button>

  <?php if ($message): ?>
    <p style="color:red; text-align:center;"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p style="text-align:center; margin-top:10px;"><a href="#" id="forgotPasswordLink">Forgot Password?</a></p>

  <div class="forgot-form" id="forgotPasswordBox">
    <?php if($forgotMessage): ?>
        <p class="message <?= strpos($forgotMessage, 'success') !== false ? 'success' : '' ?>"><?= htmlspecialchars($forgotMessage) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="email" name="forgot_email" placeholder="Enter your email" required>
        <button type="submit">Send my account number</button>
    </form>
  </div>

  <p style="text-align:center; margin-top:10px;">No account yet? <a href="consent.php">Register here</a></p>
</form>
</div>

<script>
document.getElementById("forgotPasswordLink").addEventListener("click", function(e){
    e.preventDefault();
    document.getElementById("forgotPasswordBox").classList.toggle("active");
    if(document.getElementById("forgotPasswordBox").classList.contains("active")){
        document.getElementById("forgotPasswordBox").scrollIntoView({behavior:"smooth"});
    }
});
</script>

</body>
</html>
