<?php
session_start();
include '../db_connect.php'; // adjust path if needed
include '../db_connect.php';

$message = '';

@@ -10,18 +10,16 @@

    if ($account_number && $password) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE full_name = :full_name LIMIT 1");
            $stmt->execute([':full_name' => $full_name]);
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
@@ -44,66 +42,106 @@
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: linear-gradient(to bottom right, #ccffee, #e6fff5);
  display: flex; justify-content: center; align-items: center;
  height: 100vh; margin: 0;
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
  padding: 30px;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  width: 340px;
  width: 380px;
}
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
h2 { color: #007744; text-align: center; margin-bottom: 15px; }
input {
  width: 100%; padding: 10px; margin: 8px 0;
  border-radius: 8px; border: 1px solid #ccc;
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
button {
  width: 100%;
  padding: 10px;
  background: #00aa44;
  color: white;
  background: #0099cc;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  margin-top: 15px;
}
button:hover { background: #007a99; }
.show-pass {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 13px;
  margin-top: 6px;
}
button:hover { background: #008833; }
.show-pass { display: flex; align-items: center; gap: 5px; font-size: 13px; margin-top: -5px; }
.message { color: red; text-align: center; margin-top: 10px; }
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>

<!-- 🟢 SkyTruFiber Logo Header -->
<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Inquiry</h2>
  

  <label for="full_name">Full Name:</label>
  <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
  
  <label for="password">Account No:</label>
  <input type="password" id="password" name="password" placeholder="Enter your password" required>
  
  <input type="text" id="full_name" name="full_name" placeholder="Enter full Name" required>

  <label for="password">Password:</label>
  <input type="password" id="password" name="password" placeholder="Enter password" required>

  <div class="show-pass">
    <input type="checkbox" id="showPassword"> <label for="showPassword">Show Password</label>
    <input type="checkbox" id="showPassword">
    <label for="showPassword">Show Password</label>
  </div>

  <button type="submit">Login</button>
  <button type="submit">Submit</button>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p style="text-align:center;margin-top:10px;">No account? <a href="register.php">Register here</a></p>
  <p style="text-align:center; margin-top:10px;">No account yet? <a href="register.php">Register here</a></p>
</form>

<script>
document.getElementById('showPassword').addEventListener('change', function() {
  const pwField = document.getElementById('password');
  pwField.type = this.checked ? 'text' : 'password';
  const pw = document.getElementById('password');
  pw.type = this.checked ? 'text' : 'password';
});
</script>
