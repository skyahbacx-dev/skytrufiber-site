<?php
include '../db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number']);
    $full_name = trim($_POST['full_name']);
    $district = trim($_POST['district']);
    $barangay = trim($_POST['location']);
    $password = $account_number; // automatically use account number as password

    if ($account_number && $full_name && $district && $barangay) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (account_number, full_name, password, district, barangay) VALUES (:account_number, :full_name, :password, :district, :barangay)");
            $stmt->execute([
                ':account_number' => $account_number,
                ':full_name' => $full_name,
                ':password' => $hash,
                ':district' => $district,
                ':barangay' => $barangay
            ]);

            header("Location: skytrufiber.php?registered=1");
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                $message = "⚠️ Account number already exists.";
            } else {
                $message = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $message = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - SkyTruFiber</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display: flex; align-items: center; justify-content: center;
  min-height: 100vh; margin: 0;
}
form {
  background: #fff;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  width: 380px;
}
h2 { text-align: center; color: #004466; }
label { font-weight: 600; color: #004466; display: block; margin-top: 10px; }
input, select {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
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
.message { color: red; text-align: center; margin-top: 10px; }
</style>
</head>
<body>

<form method="POST">
  <h2>SkyTruFiber Registration</h2>

  <label for="account_number">Account Number:</label>
  <input type="text" name="account_number" id="account_number" placeholder="Enter account number" required>

  <label for="full_name">Full Name:</label>
  <input type="text" name="full_name" id="full_name" placeholder="Enter your full name" required>

  <!-- 🏙️ District + Barangay Dropdown -->
  <label for="district">District:</label>
  <select id="district" name="district" required>
    <option value="">Select District</option>
    <option value="District 1">District 1</option>
    <option value="District 2">District 2</option>
    <option value="District 3">District 3</option>
    <option value="District 4">District 4</option>
    <option value="District 5">District 5</option>
    <option value="District 6">District 6</option>
  </select>

  <label for="location">Location (Barangay, Quezon City):</label>
  <select id="location" name="location" required>
    <option value="">Select your barangay</option>
    <?php include 'barangay_list.php'; ?>
  </select>

  <button type="submit">Register</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center; margin-top:10px;">Already registered? <a href="skytrufiber.php">Login here</a></p>
</form>

</body>
</html>
