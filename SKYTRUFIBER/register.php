<?php
include '../db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number']);
    $full_name = trim($_POST['full_name']);
    $district = trim($_POST['district']);
    $barangay = trim($_POST['location']);
    $password = $account_number; // automatic password = account number

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
h2 { text-align: center; color: #004466; margin-bottom: 15px; }
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
a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>

<!-- 🟢 SkyTruFiber Logo Header -->
<div class="logo-container">
  <img src="SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Registration</h2>

  <label for="account_number">Account Number:</label>
  <input type="text" name="account_number" id="account_number" placeholder="Enter account number" required>

  <label for="full_name">Full Name:</label>
  <input type="text" name="full_name" id="full_name" placeholder="Enter your full name" required>

  <!-- 🏙️ Dynamic District and Barangay Selection -->
  <label for="district">District:</label>
  <select id="district" name="district" required>
    <option value="">Select District</option>
    <option value="District 1">District 1</option>
    <option value="District 3">District 3</option>
    <option value="District 4">District 4</option>
  </select>

  <label for="location">Location (Barangay, Quezon City):</label>
  <select id="location" name="location" required>
    <option value="">Select your barangay</option>
  </select>

  <button type="submit">Submit</button>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <p style="text-align:center; margin-top:10px;">Already registered? <a href="skytrufiber.php">Login here</a></p>
</form>

<script>
// 🏙️ Barangays by District
const barangays = {
  "District 1": [
    "Alicia (Bago Bantay)", "Bagong Pag-asa", "Bahay Toro", "Balingasa", "Bungad",
    "Damar", "Damayan", "Del Monte", "Katipunan", "Lourdes", "Maharlika", "Manresa", 
    "Mariblo", "Masambong", "N.S. Amoranto", "Nayong Kanluran", "Pag-ibig sa Nayon", 
    "Paltok", "Paraiso", "Phil-Am", "Project 6", "Ramon Magsaysay", "San Antonio",
    "San Jose", "Santa Cruz", "Santa Teresita", "Talayan", "West Triangle"
  ],
 
  "District 3": [
    "Camp Aguinaldo", "San Roque", "Silangan", "Socorro", "Bagumbayan",
    "Libis", "Loyola Heights", "Matandang Balara", "Quirino 2-A", "Quirino 2-B",
    "Quirino 2-C", "Amihan", "Duyan-duyan", "Bagumbuhay", "Blue Ridge A", "White Plains"
  ],
  "District 4": [
    "Kamuning", "Kaunlaran", "Sacred Heart", "San Martin de Porres", "Santol",
    "Sikatuna Village", "South Triangle", "Teacher's Village East", "Teacher's Village West",
    "UP Campus", "UP Village", "Laging Handa", "Obrero", "Mariana", "Damayang Lagi"
  ],
};

// 🎯 Populate Barangay dropdown based on selected district
document.getElementById('district').addEventListener('change', function() {
  const selectedDistrict = this.value;
  const locationSelect = document.getElementById('location');
  locationSelect.innerHTML = '<option value="">Select your barangay</option>';

  if (barangays[selectedDistrict]) {
    barangays[selectedDistrict].forEach(brgy => {
      const option = document.createElement('option');
      option.value = brgy;
      option.textContent = brgy;
      locationSelect.appendChild(option);
    });
  }
});
</script>

</body>
</html>
