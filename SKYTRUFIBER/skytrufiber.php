<?php
session_start();
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$message = '';
$forgotMessage = '';

// LOGIN POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $email_or_name = trim($_POST['full_name']);
    $password = $_POST['password'] ?? '';
    $concern = trim($_POST['concern'] ?? '');

    if ($email_or_name && $password) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :input OR full_name = :input LIMIT 1");
            $stmt->execute([':input' => $email_or_name]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Reset ticket_status
                $updateTicket = $conn->prepare("UPDATE users SET ticket_status = 'unresolved' WHERE id = :cid");
                $updateTicket->execute([':cid' => $user['id']]);

                // SET SESSION
                $_SESSION['client_id']   = $user['id'];
                $_SESSION['client_name'] = $user['full_name'];
                $_SESSION['email']       = $user['email'];

                // INSERT first concern
                if (!empty($concern)) {
                    $insert = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at) VALUES (:cid, 'client', :msg, false, false, NOW())");
                    $insert->execute([':cid'=>$user['id'], ':msg'=>$concern]);
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
<title>SkyTruFiber Customer Portal</title>
<style>
body {
  font-family:"Segoe UI", Arial, sans-serif;
  background:linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0;
}
.container {
  background:rgba(255,255,255,0.5); padding:30px; border-radius:20px;
  backdrop-filter:blur(12px); box-shadow:0 8px 25px rgba(0,0,0,0.15);
  width:380px; text-align:center;
}
.container img { width:150px; border-radius:50%; margin-bottom:15px; }
input, textarea { width:100%; padding:10px; margin:8px 0; border-radius:10px; border:1px solid #ccc; box-sizing:border-box; }
textarea { height:80px; resize:none; }
button { width:100%; padding:12px; background:#00a6b6; color:white; border:none; border-radius:50px; cursor:pointer; font-weight:bold; font-size:16px; margin-top:10px; }
button:hover { background:#008c96; transform:translateY(-2px); }
button:active { transform:scale(.97); }
a { display:block; margin-top:10px; color:#0077a3; text-decoration:none; }
a:hover { text-decoration:underline; }
.forgot-form { max-height:0; overflow:hidden; transition:max-height 0.5s ease, padding 0.5s ease; margin-top:10px; }
.forgot-form.active { max-height:150px; padding-top:10px; }
.message { font-size:0.9em; margin-bottom:8px; }
.message.success { color:green; }
.message.error { color:red; }
</style>
</head>
<body>
<div class="container">
    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    <h2>Customer Service Portal</h2>

    <!-- LOGIN FORM -->
    <form method="POST">
        <input type="text" name="full_name" placeholder="Email or Full Name" required>
        <input type="password" name="password" placeholder="Password" required>
        <textarea name="concern" placeholder="Concern / Inquiry"></textarea>
        <button type="submit">Submit</button>
    </form>
    <?php if($message): ?>
        <p class="message error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <a href="#" id="forgotLink">Forgot Password?</a>

    <!-- FORGOT PASSWORD FORM -->
    <div class="forgot-form" id="forgotForm">
        <?php if($forgotMessage): ?>
            <p class="message <?= strpos($forgotMessage,'success')!==false?'success':'error' ?>"><?= htmlspecialchars($forgotMessage) ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="forgot_email" placeholder="Enter your email" required>
            <button type="submit">Send my account number</button>
        </form>
    </div>

    <p>No account yet? <a href="consent.php">Register here</a></p>
</div>

<script>
const forgotLink = document.getElementById('forgotLink');
const forgotForm = document.getElementById('forgotForm');
forgotLink.addEventListener('click', function(e){
    e.preventDefault();
    forgotForm.classList.toggle('active');
    if(forgotForm.classList.contains('active')){
        forgotForm.scrollIntoView({behavior:'smooth'});
    }
});
</script>
</body>
</html>
