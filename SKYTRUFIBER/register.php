<?php
include '../db_connect.php';

$message = '';
$feedback_message = '';

/* =============================
   CUSTOMER REGISTRATION SECTION
   ============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $account_number = trim($_POST['account_number']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $district = trim($_POST['district']);
    $barangay = trim($_POST['location']);
    $date_installed = trim($_POST['date_installed']);
    $password = $account_number;

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
            echo "<script>alert('✅ Registration successful! You can now submit feedback or log in.');</script>";
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

/* ===========================
   CUSTOMER FEEDBACK SECTION
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $tech_name = trim($_POST['tech_name']);
    $client_name = trim($_POST['client_name']);
    $remarks = trim($_POST['remarks']);

    if ($tech_name && $client_name && $remarks) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO survey (tech_name, client_name, remarks, created_at)
                VALUES (:tech_name, :client_name, :remarks, NOW())
            ");
            $stmt->execute([
                ':tech_name' => $tech_name,
                ':client_name' => $client_name,
                ':remarks' => $remarks
            ]);
            $feedback_message = "✅ Thank you for your feedback!";
        } catch (PDOException $e) {
            $feedback_message = "❌ Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $feedback_message = '⚠️ Please complete all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Registration & Feedback</title>
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
.container {
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
.message, .feedback-message {
  color: red;
  text-align: center;
  margin-top: 10px;
}
.feedback-message.success { color: green; }
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="logo-container">
  <img src="SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<!-- ================= REGISTRATION FORM ================= -->
<form method="POST" class="container">
  <h2>Customer Registration & Feedback</h2>
  <input type="hidden" name="register" value="1">

  <label>Account Number:</label>
  <input type="text" name="account_number" placeholder="Enter account number" required>

  <label>Full Name:</label>
  <input type="text" name="full_name" placeholder="Enter full name" required>

  <label>Email:</label>
  <input type="email" name="email" placeholder="Enter email" required>

  <label>District:</label>
  <select name="district" id="district" required>
    <option value="">Select District</option>
    <option value="District 1">District 1</option>
    <option value="District 3">District 3</option>
    <option value="District 4">District 4</option>

  </select>

  <label>Location (Barangay, QC):</label>
  <select id="location" name="location" required>
    <option value="">Select Barangay</option>
  </select>

  <label>Date Installed:</label>
  <input type="date" name="date_installed" id="date_installed" required>

  <button type="submit">Register</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center; margin-top:10px;">Already registered? <a href="skytrufiber.php">Login here</a></p>
</form>

<!-- ================= FEEDBACK FORM ================= -->
<form method="POST" class="container">
  <h2>Submit Feedback</h2>
  <input type="hidden" name="submit_feedback" value="1">
  <label>Feedback / Comments:</label>
  <textarea name="remarks" placeholder="Write your feedback here..." required></textarea>

  <button type="submit">Submit Feedback</button>
  <?php if ($feedback_message): ?>
    <p class="feedback-message <?= strpos($feedback_message, '✅') !== false ? 'success' : '' ?>">
      <?= htmlspecialchars($feedback_message) ?>
    </p>
  <?php endif; ?>
</form>

<script>
// Populate barangays dynamically
const barangays = {
  "District 1": ["Alicia", "Bagong Pag-asa", "Bahay Toro", "Balingasa", "Bungad", "Del Monte"],
  "District 3": ["Camp Aguinaldo", "San Roque", "Silangan", "Socorro", "Bagumbayan"],
  "District 4": ["Kamuning", "Kaunlaran", "Sacred Heart", "San Martin de Porres", "Santol"],
};
document.getElementById('district').addEventListener('change', function() {
  const loc = document.getElementById('location');
  loc.innerHTML = '<option value="">Select Barangay</option>';
  const selected = this.value;
  if (barangays[selected]) {
    barangays[selected].forEach(b => {
      const opt = document.createElement('option');
      opt.value = b;
      opt.textContent = b;
      loc.appendChild(opt);
    });
  }
});
// Set today as default date
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('date_installed').value = today;
});
</script>
</body>
</html>
