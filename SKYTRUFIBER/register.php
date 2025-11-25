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

#searchBar {
  width:100%; padding:10px; border-radius:8px; border:1px solid #0099cc; margin-top:6px;
}
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

  <input type="text" id="searchBar" placeholder="🔍 Search Barangay...">

  <select id="location" name="location" required size="8" style="height:auto;">

    <!-- POPULAR -->
    <optgroup label="⭐ Popular Barangays">
      <option data-district="District 1">Project 6 (Diliman / Triangle Park)</option>
      <option data-district="District 1">Bungad (Project 7)</option>
      <option data-district="District 4">Pinyahan (Triangle Park)</option>
      <option data-district="District 3">San Roque (Cubao)</option>
      <option data-district="District 4">Kamuning</option>
    </optgroup>

    <!-- DISTRICT 1 -->
    <optgroup label="District 1">
      <option data-district="District 1">Alicia (Bago Bantay)</option>
      <option data-district="District 1">Bagong Pag-asa (North EDSA / Triangle Park)</option>
      <option data-district="District 1">Bahay Toro (Project 8)</option>
      <option data-district="District 1">Balingasa (Balintawak / Cloverleaf)</option>
      <option data-district="District 1">Bungad (Project 7)</option>
      <option data-district="District 1">Damar</option>
      <option data-district="District 1">Damayan (San Francisco del Monte / Frisco)</option>
      <option data-district="District 1">Del Monte (San Francisco del Monte / Frisco)</option>
      <option data-district="District 1">Katipunan (Muñoz)</option>
      <option data-district="District 1">Lourdes (Sta. Mesa Heights)</option>
      <option data-district="District 1">Maharlika (Sta. Mesa Heights)</option>
      <option data-district="District 1">Manresa</option>
      <option data-district="District 1">Mariblo (SFDM / Frisco)</option>
      <option data-district="District 1">Masambong</option>
      <option data-district="District 1">N.S. Amoranto (Gintong Silahis, La Loma)</option>
      <option data-district="District 1">Nayong Kanluran</option>
      <option data-district="District 1">Paang Bundok (La Loma)</option>
      <option data-district="District 1">Pag-ibig sa Nayon (Balintawak)</option>
      <option data-district="District 1">Paltok (SFDM / Frisco)</option>
      <option data-district="District 1">Paraiso (SFDM / Frisco)</option>
      <option data-district="District 1">Phil-Am (West Triangle)</option>
      <option data-district="District 1">Ramon Magsaysay (Bago Bantay)</option>
      <option data-district="District 1">Saint Peter (Sta. Mesa Heights)</option>
      <option data-district="District 1">Salvacion (La Loma)</option>
      <option data-district="District 1">San Antonio (SFDM / Frisco)</option>
      <option data-district="District 1">San Isidro Labrador (La Loma)</option>
      <option data-district="District 1">San Jose (La Loma)</option>
      <option data-district="District 1">Santa Cruz (Pantranco / Heroes Hill)</option>
      <option data-district="District 1">Santa Teresita (Sta. Mesa Heights)</option>
      <option data-district="District 1">Santo Domingo (Matalahib)</option>
      <option data-district="District 1">Siena</option>
      <option data-district="District 1">Sto. Cristo (Bago Bantay)</option>
      <option data-district="District 1">Talayan</option>
      <option data-district="District 1">Vasra (Diliman)</option>
      <option data-district="District 1">Veterans Village (Project 7 / Muñoz)</option>
      <option data-district="District 1">West Triangle</option>
    </optgroup>

    <!-- DISTRICT 3 -->
    <optgroup label="District 3">
      <option data-district="District 3">Camp Aguinaldo</option>
      <option data-district="District 3">Pansol (Balara)</option>
      <option data-district="District 3">Mangga (Cubao)</option>
      <option data-district="District 3">San Roque (Cubao)</option>
      <option data-district="District 3">Silangan (Cubao)</option>
      <option data-district="District 3">Socorro (Araneta City)</option>
      <option data-district="District 3">Bagumbayan (Eastwood)</option>
      <option data-district="District 3">Libis (Eastwood)</option>
      <option data-district="District 3">Ugong Norte (Green Meadows / Corinthian / Ortigas)</option>
      <option data-district="District 3">Masagana (Jacobo Zobel)</option>
      <option data-district="District 3">Loyola Heights (Katipunan)</option>
      <option data-district="District 3">Matandang Balara (Old Balara)</option>
      <option data-district="District 3">East Kamias (Project 1)</option>
      <option data-district="District 3">Quirino 2-A (Project 2 / Anonas)</option>
      <option data-district="District 3">Quirino 2-B (Project 2 / Anonas)</option>
      <option data-district="District 3">Quirino 2-C (Project 2 / Anonas)</option>
      <option data-district="District 3">Amihan (Project 3)</option>
      <option data-district="District 3">Claro (Quirino 3-B)</option>
      <option data-district="District 3">Duyan-duyan (Project 3)</option>
      <option data-district="District 3">Quirino 3-A (Project 3 / Anonas)</option>
      <option data-district="District 3">Bagumbuhay (Project 4)</option>
      <option data-district="District 3">Bayanihan (Project 4)</option>
      <option data-district="District 3">Blue Ridge A (Project 4)</option>
      <option data-district="District 3">Blue Ridge B (Project 4)</option>
      <option data-district="District 3">Dioquino Zobel (Project 4)</option>
      <option data-district="District 3">Escopa I</option>
      <option data-district="District 3">Escopa II</option>
      <option data-district="District 3">Escopa III</option>
      <option data-district="District 3">Escopa IV</option>
      <option data-district="District 3">Marilag (Project 4)</option>
      <option data-district="District 3">Milagrosa (Project 4)</option>
      <option data-district="District 3">Tagumpay (Project 4)</option>
      <option data-district="District 3">Villa Maria Clara (Project 4)</option>
      <option data-district="District 3">E. Rodriguez (Project 5 / Cubao)</option>
      <option data-district="District 3">West Kamias (Project 5 / Kamias)</option>
      <option data-district="District 3">St. Ignatius</option>
      <option data-district="District 3">White Plains</option>
    </optgroup>

    <!-- DISTRICT 4 -->
    <optgroup label="District 4">
      <option data-district="District 4">Bagong Lipunan ng Crame (Camp Crame)</option>
      <option data-district="District 4">Botocan (Diliman)</option>
      <option data-district="District 4">Central (Diliman)</option>
      <option data-district="District 4">Damayang Lagi (New Manila)</option>
      <option data-district="District 4">Don Manuel (Galas)</option>
      <option data-district="District 4">Doña Aurora (Galas)</option>
      <option data-district="District 4">Doña Imelda (Sta. Mesa / Galas)</option>
      <option data-district="District 4">Doña Josefa (Galas)</option>
      <option data-district="District 4">Horseshoe</option>
      <option data-district="District 4">Immaculate Concepcion (Cubao)</option>
      <option data-district="District 4">Kalusugan (St. Luke’s)</option>
      <option data-district="District 4">Kamuning</option>
      <option data-district="District 4">Kaunlaran (Cubao)</option>
      <option data-district="District 4">Kristong Hari</option>
      <option data-district="District 4">Krus na Ligas (Diliman)</option>
      <option data-district="District 4">Laging Handa (Diliman)</option>
      <option data-district="District 4">Malaya (Diliman)</option>
      <option data-district="District 4">Mariana (New Manila)</option>
      <option data-district="District 4">Obrero (Project 1)</option>
      <option data-district="District 4">Old Capitol Site (Diliman)</option>
      <option data-district="District 4">Paligsahan (Diliman)</option>
      <option data-district="District 4">Pinagkaisahan (Cubao)</option>
      <option data-district="District 4">Pinyahan (Triangle Park)</option>
      <option data-district="District 4">Roxas (Project 1)</option>
      <option data-district="District 4">Sacred Heart (Kamuning)</option>
      <option data-district="District 4">San Isidro Galas (Galas)</option>
      <option data-district="District 4">San Martin de Porres (Cubao)</option>
      <option data-district="District 4">San Vicente (Diliman)</option>
      <option data-district="District 4">Santol</option>
      <option data-district="District 4">Sikatuna Village (Diliman)</option>
      <option data-district="District 4">South Triangle (Diliman)</option>
      <option data-district="District 4">Sto. Niño (Galas)</option>
      <option data-district="District 4">Tatalon</option>
      <option data-district="District 4">Teacher's Village East (Diliman)</option>
      <option data-district="District 4">Teacher's Village West (Diliman)</option>
      <option data-district="District 4">U.P. Campus (Diliman)</option>
      <option data-district="District 4">U.P. Village (Diliman)</option>
      <option data-district="District 4">Valencia (Gilmore / N. Domingo)</option>
    </optgroup>

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
// Auto fill today's date
document.addEventListener("DOMContentLoaded", () => {
  const d = new Date();
  document.getElementById("date_installed").value =
    d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});

// SEARCH FILTER
document.getElementById("searchBar").addEventListener("keyup", function () {
  const filter = this.value.toLowerCase();
  const options = document.querySelectorAll("#location option");

  options.forEach(opt => {
    opt.style.display = opt.textContent.toLowerCase().includes(filter) ? "" : "none";
  });
});

// AUTO FILL DISTRICT
document.getElementById("location").addEventListener("change", function () {
  const selected = this.options[this.selectedIndex];
  const dist = selected.getAttribute("data-district");
  if (dist) document.getElementById("district").value = dist;
});
</script>

</body>
</html>
