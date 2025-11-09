<?php
session_start();
include '../db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($account_number && $password) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE account_number = :account_number LIMIT 1");
            $stmt->execute([':account_number' => $account_number]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user['account_number'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['district'] = $user['district'] ?? '';
                $_SESSION['barangay'] = $user['barangay'] ?? '';

                header("Location: skytrufiber.php");
                exit;
            } else {
                $message = "❌ Invalid account number or password.";
            }
        } catch (PDOException $e) {
            $message = "⚠️ Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "⚠️ Please enter both account number and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Login</title>
<style>
/* layout */
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
  width: 380px;
}

/* headings + labels */
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

/* inputs */
input[type="text"],
input[type="password"] {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

/* button */
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

/* show password row: right aligned, vertically centered */
.show-pass {
  display: flex;
  justify-content: flex-end;    /* push to the right */
  align-items: center;           /* vertical center */
  gap: 8px;
  margin-top: 8px;
  font-size: 13px;
  color: #004466;
}

/* smaller checkbox so it looks neat */
.show-pass input[type="checkbox"] {
  width: 16px;
  height: 16px;
  margin: 0;
  vertical-align: middle;
}

/* message and links */
.message { color: red; text-align: center; margin-top: 10px; }
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }

/* responsive tweaks */
@media (max-width:420px){
  form { width: 92%; padding: 18px; }
  .logo-container img { width: 120px; }
}
</style>
</head>
<body>

<!-- 🟢 SkyTruFiber Logo Header -->
<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Inquiry</h2>

  <!-- note: field names kept as your PHP expects -->
  <label for="account_number">Account Number / Full Name:</label>
  <input type="text" id="account_number" name="account_number" placeholder="Enter account number or full name" required>

  <label for="password">Password:</label>
  <input type="password" id="password" name="password" placeholder="Enter password" required>

  <!-- RIGHT-ALIGNED Show Password -->
  <div class="show-pass">
    <label for="showPassword">Show Password</label>
    <input type="checkbox" id="showPassword" />
  </div>

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
