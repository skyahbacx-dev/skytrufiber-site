<?php
session_start();
include '../db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // email or full name
    $password = $_POST['password'] ?? '';
    $concern = trim($_POST['concern'] ?? '');

    if ($identifier && $password && $concern) {
        try {
            // ✅ Check if customer is registered (email OR full name)
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :identifier OR full_name = :identifier LIMIT 1");
            $stmt->execute([':identifier' => $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // ✅ Valid customer → store info in session
                $_SESSION['user'] = $user['account_number'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'] ?? '';
                $_SESSION['district'] = $user['district'] ?? '';
                $_SESSION['barangay'] = $user['barangay'] ?? '';
                $_SESSION['concern'] = $concern;

                // Redirect to chat page
                header("Location: chat_support.php");
                exit;
            } else {
                $message = "❌ Invalid email/full name or password. Please make sure you’re registered.";
            }
        } catch (PDOException $e) {
            $message = "⚠️ Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "⚠️ Please complete all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Customer Service</title>
<style>
/* Layout */
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  margin: 0;
}
.logo-container {
  text-align: center;
  margin-bottom: 15px;
}
.logo-container img {
  width: 140px;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
form {
  background: #fff;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  width: 400px;
}

/* Headings + Labels */
h2 {
  color: #004466;
  text-align: center;
  margin-bottom: 15px;
}
label {
  font-weight: 600;
  color: #004466;
  display: block;
  margin-top: 10px;
}

/* Inputs */
input[type="text"],
input[type="password"],
textarea {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
  box-sizing: border-box;
  font-size: 14px;
}
textarea {
  resize: vertical;
  min-height: 80px;
}

/* Button */
button {
  width: 100%;
  padding: 10px;
  background: #0099cc;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  margin-top: 15px;
}
button:hover { background: #007a99; }

/* Show Password */
.show-pass {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  font-size: 13px;
  color: #004466;
}
.show-pass input[type="checkbox"] {
  width: 16px;
  height: 16px;
  margin: 0;
}

/* Message + Links */
.message { color: red; text-align: center; margin-top: 10px; }
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }

/* Responsive */
@media (max-width:420px){
  form { width: 92%; padding: 18px; }
  .logo-container img { width: 120px; }
}
</style>
</head>
<body>

<!-- 🟢 SkyTruFiber Logo -->
<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Service Portal</h2>

  <label for="identifier">Email or Full Name:</label>
  <input type="text" id="identifier" name="identifier" placeholder="Enter your email or full name" required>

  <label for="password">Password:</label>
  <input type="password" id="password" name="password" placeholder="Enter password" required>

  <div class="show-pass">
    <label for="showPassword">Show Password</label>
    <input type="checkbox" id="showPassword" />
  </div>

  <label for="concern">Your Concern / Inquiry:</label>
  <textarea id="concern" name="concern" placeholder="Describe your concern or issue here..." required></textarea>

  <button type="submit">Submit</button>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p style="text-align:center; margin-top:10px;">No account yet? <a href="register.php">Register here</a></p>
</form>

<script>
document.getElementById('showPassword').addEventListener('change', function() {
  const pw = document.getElementById('password');
  pw.type = this.checked ? 'text' : 'password';
});
</script>

</body>
</html>
