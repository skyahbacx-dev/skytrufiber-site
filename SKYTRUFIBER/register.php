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
    $password = $account_number;

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {

        try {
            $conn->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("
                INSERT INTO users (account_number, full_name, email, password, district, barangay, date_installed, privacy_consent, created_at)
                VALUES (:acc, :name, :email, :pw, :district, :brgy, :installed, 'yes', NOW())
            ");
            $stmt->execute([
                ':acc' => $account_number,
                ':name' => $full_name,
                ':email' => $email,
                ':pw' => $hash,
                ':district' => $district,
                ':brgy' => $barangay,
                ':installed' => $date_installed
            ]);

            if ($remarks) {
                $stmt2 = $conn->prepare("
                    INSERT INTO survey_responses (client_name, account_number, district, location, feedback, created_at)
                    VALUES (:name, :acc, :district, :brgy, :feedback, NOW())
                ");
                $stmt2->execute([
                    ':name' => $full_name,
                    ':acc' => $account_number,
                    ':district' => $district,
                    ':brgy' => $barangay,
                    ':feedback' => $remarks
                ]);
            }

            $conn->commit();
            header("Location: skytrufiber.php?msg=success");
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $message = '❌ Database error: ' . htmlspecialchars($e->getMessage());
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
  display:flex; flex-direction:column; align-items:center; justify-content:flex-start;
  min-height: 100vh; margin:0; padding-top:30px;
}

form {
  background:#fff; padding:25px; border-radius:15px; width:380px;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
}

h2 { text-align:center; color:#004466; margin-bottom:15px; }
label { font-weight:600; color:#004466; display:block; margin-top:10px; }
input, select, textarea {
  width:100%; padding:10px; margin-top:5px; border-radius:8px; border:1px solid #ccc;
}
button {
  width:100%; padding:10px; background:#0099cc; color:white; border:none;
  border-radius:8px; cursor:pointer; margin-top:15px; font-weight:bold;
}
textarea { height:80px; resize:none; }
</style>

</head>
<body>

<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo" style="width:140px; border-radius:50%; margin-bottom:15px;">
</div>

<form method="POST">
  <h2>Customer Registration & Feedback</h2>

  <label>Account Number:</label>
  <input type="text" name="account_number" required>

  <label>Full Name:</label>
  <input type="text" name="full_name" required>

  <label>Email:</label>
  <input type="email" name="email" required>

  <label>District:</label>
  <select id="district" name="district" required>
    <option value="">Select District</option>
    <option value="District 1">District 1</option>
    <option value="District 3">District 3</option>
    <option value="District 4">District 4</option>
  </select>

  <label>Barangay:</label>
  <select id="location" name="location" required>
    <option value="">Select Barangay</option>
  </select>

  <label>Date Installed:</label>
  <input type="date" id="date_installed" name="date_installed" required>

  <label>Feedback / Comments:</label>
  <textarea name="remarks" required></textarea>

  <button type="submit">Submit</button>

  <?php if ($message): ?>
    <p style="color:red; text-align:center;"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p style="text-align:center; margin-top:10px;">Already registered? <a href="skytrufiber.php">Login here</a></p>
</form>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const d = new Date();
  document.getElementById("date_installed").value =
    d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
