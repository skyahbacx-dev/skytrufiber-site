<?php
session_start();
include '../db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Normal login POST
    if (isset($_POST['full_name']) && isset($_POST['password'])) {

        $email_or_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $concern = trim($_POST['concern'] ?? '');

        if ($email_or_name && $password) {
            try {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = :input OR full_name = :input LIMIT 1");
                $stmt->execute([':input' => $email_or_name]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {

                    // Reset ticket_status when logging in again (NEW TICKET)
                    $updateTicket = $conn->prepare("
                        UPDATE users SET ticket_status = 'unresolved'
                        WHERE id = :cid
                    ");
                    $updateTicket->execute([':cid' => $user['id']]);

                    // SET SESSION
                    $_SESSION['client_id']   = $user['id'];
                    $_SESSION['client_name'] = $user['full_name'];
                    $_SESSION['email']       = $user['email'];

                    // INSERT first concern message
                    if (!empty($concern)) {
                        $insert = $conn->prepare("
                            INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
                            VALUES (:cid, 'client', :msg, false, false, NOW())
                        ");
                        $insert->execute([
                            ':cid' => $user['id'],
                            ':msg' => $concern
                        ]);
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
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  min-height:100vh; margin:0;
}

@keyframes slideLogo { from { opacity:0; transform:translateY(-60px); } to { opacity:1; transform:translateY(0); } }
.logo { animation: slideLogo .9s ease-out forwards; }

form {
  background:rgba(255,255,255,0.45);
  padding:25px;
  border-radius:20px;
  width:380px;
  backdrop-filter:blur(12px);
  box-shadow:0 8px 25px rgba(0,0,0,0.15);
  opacity:0;
  transform:translateY(40px);
}
.showForm { animation:fadeSlide .6s ease forwards; }

@keyframes fadeSlide { from { opacity:0; transform:translateY(40px); } to { opacity:1; transform:translateY(0); } }

input, textarea {
  width:100%; padding:10px; margin-top:5px;
  border-radius:10px; border:1px solid #ccc; box-sizing:border-box;
}
textarea { height:80px; resize:none; }

button { width:100%; padding:12px; background:#00a6b6; color:white; border:none;
  border-radius:50px; cursor:pointer; font-weight:bold; font-size:16px; margin-top:15px; }
button:hover { background:#008c96; transform:translateY(-2px); }
button:active { transform:scale(.97); }

label { display:block; margin-top:10px; color:#004466; font-weight:600; }
</style>
</head>

<body>

<img src="../SKYTRUFIBER.png" class="logo" style="width:150px; border-radius:50%; margin-bottom:15px;">

<form id="supportForm" method="POST">
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

  <p style="text-align:center; margin-top:10px;">
    <a href="#" id="forgotPasswordLink">Forgot Password?</a>
  </p>

  <div id="forgotPasswordBox" style="display:none; margin-top:10px; text-align:center;">
      <input type="email" id="forgotEmail" placeholder="Enter your email"
             style="width:90%; padding:8px; border-radius:8px;">
      <button type="button" id="sendForgot"
              style="margin-top:10px; padding:8px 20px; border-radius:20px;">
          Send my account number
      </button>
  </div>

  <p style="text-align:center; margin-top:10px;">
      No account yet? <a href="consent.php">Register here</a>
  </p>

</form>

<script>
document.getElementById("supportForm").classList.add("showForm");

// SHOW FORGOT PASSWORD FIELD
document.getElementById("forgotPasswordLink").addEventListener("click", function(e) {
    e.preventDefault();
    document.getElementById("forgotPasswordBox").style.display = "block";
});

// SEND FORGOT PASSWORD REQUEST
document.getElementById("sendForgot").addEventListener("click", function () {
    const email = document.getElementById("forgotEmail").value.trim();
    if (!email) {
        alert("Please enter your email.");
        return;
    }

    fetch("forgot_password.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "email=" + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(err => console.error(err));
});
</script>

</body>
</html>
