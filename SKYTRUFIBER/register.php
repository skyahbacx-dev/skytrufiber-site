<?php
include 'db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];

    if ($account_number && $full_name && $password) {
        // Hash password
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (account_number, full_name, password) VALUES (:account_number, :full_name, :password)");
            $stmt->execute([
                ':account_number' => $account_number,
                ':full_name' => $full_name,
                ':password' => $hash
            ]);

            header("Location: skytrufiber.php?registered=1");
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                $message = "Account number already exists.";
            } else {
                $message = "Error: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $message = "Please fill out all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - SkyTruFiber</title>
<style>
body { font-family: Arial; background: #e6f7ff; display: flex; justify-content: center; align-items: center; height: 100vh; }
form { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.2); width: 320px; }
h2 { color: #0077b3; text-align: center; }
input { width: 100%; padding: 10px; margin: 8px 0; border-radius: 8px; border: 1px solid #ccc; }
button { width: 100%; padding: 10px; background: #0099cc; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
button:hover { background: #007a99; }
.message { color: red; text-align: center; margin-top: 8px; }
</style>
</head>
<body>
<form method="POST">
  <h2>Register Account</h2>
  <input type="text" name="account_number" placeholder="Enter account number" required>
  <input type="text" name="full_name" placeholder="Enter full name" required>
  <input type="password" name="password" placeholder="Enter password" required>
  <button type="submit">Register</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center;margin-top:10px;">Already have an account? <a href="skytrufiber.php">Login</a></p>
</form>
</body>
</html>
