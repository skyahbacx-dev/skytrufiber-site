<?php
session_start();
include '../db_connect.php'; // adjust path if needed

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
                // ✅ Store minimal session info
                $_SESSION['user'] = $user['account_number'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['district'] = $user['district'] ?? '';
                $_SESSION['barangay'] = $user['barangay'] ?? '';

                // Redirect to SkyTruFiber dashboard
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
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: linear-gradient(to bottom right, #ccffee, #e6fff5);
  display: flex; justify-content: center; align-items: center;
  height: 100vh; margin: 0;
}
form {
  background: #fff;
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  width: 340px;
}
h2 { color: #007744; text-align: center; margin-bottom: 15px; }
input {
  width: 100%; padding: 10px; margin: 8px 0;
  border-radius: 8px; border: 1px solid #ccc;
}
button {
  width: 100%;
  padding: 10px;
  background: #00aa44;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
}
button:hover { background: #008833; }
.show-pass { display: flex; align-items: center; gap: 5px; font-size: 13px; margin-top: -5px; }
.message { color: red; text-align: center; margin-top: 10px; }
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>
<form method="POST">
  <h2>SkyTruFiber Login</h2>
  
  <label for="account_number">Account Number:</label>
  <input type="text" id="account_number" name="account_number" placeholder="Enter your account number" required>
  
  <label for="password">Password:</label>
  <input type="password" id="password" name="password" placeholder="Enter your password" required>
  
  <div class="show-pass">
    <input type="checkbox" id="showPassword"> <label for="showPassword">Show Password</label>
  </div>

  <button type="submit">Login</button>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p style="text-align:center;margin-top:10px;">No account? <a href="register.php">Register here</a></p>
</form>

<script>
document.getElementById('showPassword').addEventListener('change', function() {
  const pwField = document.getElementById('password');
  pwField.type = this.checked ? 'text' : 'password';
});
</script>

</body>
</html>
