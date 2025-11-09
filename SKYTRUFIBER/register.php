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
    $remarks = trim($_POST['remarks']);
    $password = $account_number; // auto password

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Start transaction (so both inserts happen together)
            $conn->beginTransaction();

            // 1️⃣ Register user
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

            // 2️⃣ Save feedback in survey table (no rating)
            if ($remarks) {
                $stmt2 = $conn->prepare("
                    INSERT INTO survey (tech_name, client_name, remarks, created_at)
                    VALUES (:tech_name, :client_name, :remarks, NOW())
                ");
                $stmt2->execute([
                    ':tech_name' => 'N/A', // no technician info at registration
                    ':client_name' => $full_name,
                    ':remarks' => $remarks
                ]);
            }

            $conn->commit();
            echo "<script>alert('✅ Registration and feedback submitted successfully!'); window.location='skytrufiber.php';</script>";
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                $message = '⚠️ Account number already exists.';
            } else {
                $message = '❌ Database error: ' . htmlspecialchars($e->getMessage());
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
<title>Customer Registration & Feedback - SkyTruFiber</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: start;
  min-height: 100vh;
  margin: 0;
  padding-top: 30px;
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
  margin-top: 20px;
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
input, select, textarea {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 14px;
}
textarea { resize: none; height: 80px; }
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
.message {
  color: red;
  text-align: center;
  margin-top: 10px;
}
.success { color: green; }
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Registration & Feedback</h2>

  <label for="account_number">Account Number:</label>
  <input type="text" name="account_number" placeholder="Enter account number" required>

  <label for="full_name">Full Name:</label>
  <input type="text" name="full_name" placeholder="Enter your full name" required>

  <label for="email">Email Address:</label>
  <input type="email" name="email" placeholder="Enter your email" required>

  <label for="district">District:</label>
  <select id="district" name="district" required>
    <option value="">Select District</option>
    <option value="District 1">District 1</option>

    <option value="District 3">District 3</option>
    <option value="District 4">District 4</option>

  </select>

  <label for="location">Barangay (Quezon City):</label>
  <select id="location" name="location" required>
    <option value="">Select Barangay</option>
  </select>

  <label for="date_installed">📅 Date Installed:</label>
  <input type="date" id="date_installed" name="date_installed" required>

  <label for="remarks">📝 Feedback / Comments:</label>
  <textarea name="remarks" placeholder="Write your feedback here..." required></textarea>

  <button type="submit">Submit</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center; margin-top:10px;">Already registered? <a href="skytrufiber.php">Login here</a></p>
</form>

<script>
// --- Barangays by District ---
const barangays = {
  "District 1": ["Alicia", "Bagong Pag-asa", "Bahay Toro", "Balingasa", "Bungad", "Del Monte"],
  "District 3": ["Camp Aguinaldo", "San Roque", "Silangan", "Socorro", "Bagumbayan"],
  "District 4": ["Kamuning", "Kaunlaran", "Sacred Heart", "San Martin de Porres", "Santol"],
};

document.getElementById('district').addEventListener('change', function() {
  const selected = this.value;
  const locationSelect = document.getElementById('location');
  locationSelect.innerHTML = '<option value="">Select Barangay</option>';
  if (barangays[selected]) {
    barangays[selected].forEach(b => {
      const opt = document.createElement('option');
      opt.value = b; opt.textContent = b;
      locationSelect.appendChild(opt);
    });
  }
});

// --- Auto-fill today's date ---
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  document.getElementById('date_installed').value = `${yyyy}-${mm}-${dd}`;
});
</script>

</body>
</html>
