<?php
session_start();
include 'db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE account_number = :account_number LIMIT 1");
    $stmt->execute([':account_number' => $account_number]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['account_number'];
        $_SESSION['name'] = $user['full_name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "Invalid account number or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Login</title>
<style>
body { font-family: Arial; background: #ccffee; display: flex; justify-content: center; align-items: center; height: 100vh; }
form { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.2); width: 320px; }
h2 { color: #007744; text-align: center; }
input { width: 100%; padding: 10px; margin: 8px 0; border-radius: 8px; border: 1px solid #ccc; }
button { width: 100%; padding: 10px; background: #009933; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
button:hover { background: #007a00; }
.message { color: red; text-align: center; margin-top: 8px; }
</style>
</head>
<body>
<form method="POST">
  <h2>SkyTruFiber Login</h2>
  <input type="text" name="account_number" placeholder="Enter account number" required>
  <input type="password" name="password" placeholder="Enter password" required>
  <button type="submit">Login</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center;margin-top:10px;">No account? <a href="register.php">Register here</a></p>
</form>
</body>
</html>
