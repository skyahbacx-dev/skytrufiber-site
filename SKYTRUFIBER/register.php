<?php
include '../db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $district = trim($_POST['district']);
    $barangay = trim($_POST['location']);
    $date_installed = trim($_POST['date_installed']);
    $password = $account_number; // automatic password = account number

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $conn->prepare("
                INSERT INTO users (account_number, full_name, email, password, district, barangay, date_installed, created_at)
                VALUES (:account_number, :full_name, :email, :password, :district, :barangay, :date_installed, NOW())
            ");
            $stmt->execute([
                ':account_number' => $account_number,
                ':full_name' => $full_name,
                ':email' => $email,
                ':password' => $hash,
                ':district' => $district,
                ':barangay' => $barangay,
                ':date_installed' => $date_installed
            ]);

            echo "<script>
                    alert('✅ Registration successful!');
                    window.location.href='skytrufiber.php';
                  </script>";
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                $message = '⚠️ Account number already exists.';
            } else {
                $message = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $message = '⚠️ Please fill in all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Registration - SkyTruFiber</title>
<style>
body {
  font-family: Arial, sans-serif;
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
h2 {
  text-align: center;
  color: #004466;
  margin-bottom: 15px;
}
label {
  font-weight: 600;
  color: #004466;
  display: block;
  margin-top: 10px;
}
input, select {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 14px;
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
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="logo-container">
  <img src="SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Registration</h2>

  <label for="account_number">Account Number:</label>
  <input type="text" name="account_number" id="account_number" placeholder="Enter account number" required>

  <label for="full_name">Full Name:</label>
  <input type="text" name="full_name" id="full_name" placeholder="Enter your full name" required>

  <label for="email">Email Address:</label>
  <input type="email" name="email" id="email" placeholder="Enter your email" required>

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
  </select>

  <label for="date_installed">📅 Date Installed:</label>
  <input type="date" id="date_installed" name="date_installed" required>

  <button type="submit">Register</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center; margin-top:10px;">Already registered? <a href="skytrufiber.php">Login here</a></p>
</form>

<script>
// --- Barangays by District ---
const barangays = {
  "District 1": ["Alicia (Bago Bantay)", "Bagong Pag-asa", "Bahay Toro", "Balingasa", "Bungad", "Del Monte"],
  "District 2": ["Bagong Silangan", "Batasan Hills", "Commonwealth", "Holy Spirit", "Payatas"],
  "District 3": ["Camp Aguinaldo", "San Roque", "Silangan", "Socorro", "Bagumbayan"],
  "District 4": ["Kamuning", "Kaunlaran", "Sacred Heart", "San Martin de Porres", "Santol"],
  "District 5": ["Fairview", "North Fairview", "Greater Lagro", "Sta. Lucia", "San Bartolome"],
  "District 6": ["Culiat", "Tandang Sora", "Sauyo", "Talipapa", "New Era"]
};

document.getElementById('district').addEventListener('change', function() {
  const selected = this.value;
  const locationSelect = document.getElementById('location');
  locationSelect.innerHTML = '<option value="">Select your barangay</option>';
  if (barangays[selected]) {
    barangays[selected].forEach(b => {
      const opt = document.createElement('option');
      opt.value = b; opt.textContent = b;
      locationSelect.appendChild(opt);
    });
  }
});

// --- Auto-fill date installed with today's date ---
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  const formattedDate = `${yyyy}-${mm}-${dd}`;
  document.getElementById('date_installed').value = formattedDate;
});
</script>

</body>
</html>
