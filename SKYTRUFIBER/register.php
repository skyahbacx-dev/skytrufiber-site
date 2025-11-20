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
    $password = $account_number;

    if ($privacy_consent !== "yes") {
        header("Location: skytrufiber.php?msg=success");
        exit;
    }

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {

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
  display:none; opacity:0; transform:translateY(40px);
}

.showAnimated {
  display:block !important;
  animation: slideFade .6s ease forwards;
}

@keyframes slideFade {
  from {opacity:0; transform:translateY(40px);}
  to {opacity:1; transform:translateY(0);}
}

/* FULL SCREEN PRIVACY */
#privacyScreen {
  position:fixed; top:0; left:0; width:100%; height:100vh;
  background:rgba(0,0,0,0.45); backdrop-filter:blur(10px);
  display:flex; justify-content:center; align-items:center; z-index:9999;
}

@keyframes pop {
  from {transform:scale(0.7); opacity:0;}
  to   {transform:scale(1); opacity:1;}
}
</style>
</head>

<body>

<!-- PRIVACY CONSENT FIRST -->
<div id="privacyScreen">
  <div style="background:white; padding:25px 35px; border-radius:12px; width:500px;
              animation:pop .35s ease-out; text-align:center;">
    <h2 style="color:#003d66; margin-top:0;">Data Privacy Notice</h2>
    <p style="font-size:15px; text-align:justify;">
      SkyTruFiber is committed to protecting your personal information in accordance with the Data Privacy Act of 2012 (RA 10173).
      We collect and process your data for installation, service updates, billing, and support purposes. Your information will not be shared externally without consent.
    </p>

    <div style="margin-top:25px; text-align:left;">
      <label><input type="radio" name="consentChoice" value="yes"> YES, I agree</label><br>
      <label style="margin-top:10px;"><input type="radio" name="consentChoice" value="no"> NO, I do not agree</label>
    </div>

    <button onclick="continueFlow()" style="margin-top:18px; padding:10px 25px;
      background:#0099cc; color:white; border:none; border-radius:8px; cursor:pointer;">Continue</button>
  </div>
</div>

<!-- MAIN FORM -->
<div class="logo-container">
  <img src="../SKYTRUFIBER.png" style="width:140px; border-radius:50%; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
</div>

<form method="POST" id="regForm">
  <h2 style="text-align:center; color:#004466;">Customer Registration & Feedback</h2>

  <label>Account Number:</label>
  <input type="text" name="account_number" required>

  <label>Full Name:</label>
  <input type="text" name="full_name" required>

  <label>Email Address:</label>
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

  <input type="hidden" name="privacy_consent" value="yes">

  <button type="submit" style="margin-top:15px; padding:10px; background:#0099cc; color:white; border:none; border-radius:8px;">Submit</button>
</form>

<script>
function continueFlow() {
  const choice = document.querySelector('input[name="consentChoice"]:checked');
  if (!choice) { alert("⚠ Please select YES or NO"); return; }

  if (choice.value === "yes") {
    document.getElementById("privacyScreen").style.opacity="0";
    setTimeout(() => {
      document.getElementById("privacyScreen").style.display="none";
      document.getElementById("regForm").classList.add("showAnimated");
    }, 300);
  } else {
    window.location.href = "skytrufiber.php?msg=success";
  }
}

// Auto date
document.addEventListener("DOMContentLoaded", () => {
  const today = new Date();
  document.getElementById("date_installed").value =
    today.getFullYear()+"-"+String(today.getMonth()+1).padStart(2,"0")+"-"+String(today.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
