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
    $privacy_consent = $_POST['privacy_consent'] ?? null;
    $password = $account_number; // auto password

    // Privacy validation
    if ($privacy_consent !== "yes") {
        $message = '⚠️ You must select YES to the Data Privacy Consent before submitting.';
    } elseif ($account_number && $full_name && $email && $district && $barangay && $date_installed) {

        try {
            $conn->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("
                INSERT INTO users (account_number, full_name, email, password, district, barangay, date_installed, privacy_consent, created_at)
                VALUES (:acc, :name, :email, :pw, :district, :brgy, :installed, :consent, NOW())
            ");
            $stmt->execute([
                ':acc' => $account_number,
                ':name' => $full_name,
                ':email' => $email,
                ':pw' => $hash,
                ':district' => $district,
                ':brgy' => $barangay,
                ':installed' => $date_installed,
                ':consent' => $privacy_consent
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
            echo "<script>alert('✅ Registration and feedback submitted successfully!'); window.location='skytrufiber.php';</script>";
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
</style>
</head>
<body>

<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Registration & Feedback</h2>

  <label for="account_number">Account Number:</label>
  <input type="text" name="account_number" required>

  <label for="full_name">Full Name:</label>
  <input type="text" name="full_name" required>

  <label for="email">Email Address:</label>
  <input type="email" name="email" required>

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
  <textarea name="remarks" required></textarea>

<!-- PRIVACY NOTICE SECTION -->
<div style="margin-top:20px; background:#e8f4ff; border-left:6px solid #0077cc; padding:15px; border-radius:8px; position:relative;">
  <h3 style="margin-top:0; color:#003d66;">Data Privacy Notice *</h3>
  <p style="font-size:14px; color:#002233; text-align:justify;">
    SkyTruFiber complies with the Data Privacy Act (RA 10173). Your provided information will be collected and stored securely 
    and used only for service delivery, billing notifications, updates, and customer support.
    <br><br>
    <a href="#" id="openPolicy" style="color:#0055aa; font-weight:bold;">View Full Privacy Policy</a>
  </p>
  <img src="../DPO-DPS.png" style="width:120px; position:absolute; top:15px; right:15px;">
</div>

<div style="margin-top:15px; font-size:14px; color:#002233;">
  <label><input type="radio" name="privacy_consent" value="yes" required> <strong>YES</strong>, I fully consent.</label>
</div>
<div style="margin-top:10px; font-size:14px; color:#002233;">
  <label><input type="radio" name="privacy_consent" value="no"> <strong>NO</strong>, I do not agree.</label>
</div>

  <button type="submit">Submit</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center; margin-top:10px;">Already registered? <a href="skytrufiber.php">Login here</a></p>
</form>


<!-- MODAL PRIVACY POLICY -->
<div id="policyModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:1000;">
  <div style="background:white; width:600px; max-height:80vh; padding:20px; border-radius:10px; overflow-y:auto;">
    <h2>SkyTruFiber Data Privacy Policy</h2>
    <p style="text-align:justify;">
      SkyTruFiber values and secures your personal information including name, contact details, service address, and billing records.
      This information will only be used for service operations and support. You may contact our Data Protection Officer at
      support@skytrufiber.ph for inquiries or data removal.
    </p>
    <button id="closePolicy" style="margin-top:10px; padding:8px 15px; background:#006699; color:white; border:none; border-radius:5px; cursor:pointer;">
      Close
    </button>
  </div>
</div>

<script>
document.getElementById("openPolicy").addEventListener("click", () => {
  document.getElementById("policyModal").style.display = "flex";
});
document.getElementById("closePolicy").addEventListener("click", () => {
  document.getElementById("policyModal").style.display = "none";
});

// Barangay Loading by District
const barangays = {
  "District 1": ["Alicia (Bago Bantay)", "Bagong Pag-asa (North EDSA / Triangle Park)", "Bahay Toro (Project 8)"],
  "District 3": ["Amihan", "Bagumbayan", "Bagumbuhay"],
  "District 4": ["Bagong Lipunan ng Crame", "Botocan", "Central"]
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

// Auto fill today's date
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date();
  document.getElementById('date_installed').value =
    today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');
});
</script>

</body>
</html>
