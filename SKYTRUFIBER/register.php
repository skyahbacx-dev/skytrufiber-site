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
  display:flex; flex-direction:column; align-items:center;
  justify-content:flex-start; min-height:100vh; margin:0; padding-top:30px;
}

form {
  background:#fff; padding:25px; border-radius:15px; width:380px;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
}

label { font-weight:600; color:#004466; display:block; margin-top:10px; }
input, select, textarea {
  width:100%; padding:10px; margin-top:5px; border-radius:8px; border:1px solid #ccc;
}
button {
  width:100%; padding:10px; background:#0099cc; color:white; border:none;
  border-radius:8px; font-weight:bold; cursor:pointer; margin-top:15px;
}
textarea { height:80px; resize:none; }

#searchBar { width:100%; padding:10px; border:1px solid #0099cc; border-radius:8px; margin-top:5px;}
</style>
</head>

<body>

<img src="../SKYTRUFIBER.png" style="width:140px; border-radius:50%; margin-bottom:15px;">

<form method="POST">
  <h2 style="text-align:center;">Customer Registration & Feedback</h2>

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
  <input type="text" id="searchBar" placeholder="🔍 Search barangay...">

  <select id="location" name="location" required size="8" style="height:auto;">

    <!-- Popular items categorized per district -->
    <optgroup label="⭐ Popular - District 1" data-group="District 1">
      <option data-district="District 1">Project 6 (Diliman / Triangle Park)</option>
      <option data-district="District 1">Bungad (Project 7)</option>
    </optgroup>

    <optgroup label="⭐ Popular - District 3" data-group="District 3">
      <option data-district="District 3">San Roque (Cubao)</option>
    </optgroup>

    <optgroup label="⭐ Popular - District 4" data-group="District 4">
      <option data-district="District 4">Pinyahan (Triangle Park)</option>
      <option data-district="District 4">Kamuning</option>
    </optgroup>

    <!-- DISTRICT 1 FULL -->
    <optgroup label="District 1" data-group="District 1">
      <?php
      $district1 = [
        "Alicia (Bago Bantay)","Bagong Pag-asa (North EDSA / Triangle Park)","Bahay Toro (Project 8)",
        "Balingasa (Balintawak / Cloverleaf)","Bungad (Project 7)","Damar","Damayan (San Francisco del Monte / Frisco)",
        "Del Monte (San Francisco del Monte / Frisco)","Katipunan (Muñoz)","Lourdes (Sta. Mesa Heights)",
        "Maharlika (Sta. Mesa Heights)","Manresa","Mariblo (SFDM / Frisco)","Masambong",
        "N.S. Amoranto (Gintong Silahis, La Loma)","Nayong Kanluran","Paang Bundok (La Loma)",
        "Pag-ibig sa Nayon (Balintawak)","Paltok (SFDM / Frisco)","Paraiso (SFDM / Frisco)","Phil-Am (West Triangle)",
        "Ramon Magsaysay (Bago Bantay)","Saint Peter (Sta. Mesa Heights)","Salvacion (La Loma)",
        "San Antonio (SFDM / Frisco)","San Isidro Labrador (La Loma)","San Jose (La Loma)",
        "Santa Cruz (Pantranco / Heroes Hill)","Santa Teresita (Sta. Mesa Heights)","Santo Domingo (Matalahib)",
        "Siena","Sto. Cristo (Bago Bantay)","Talayan","Vasra (Diliman)",
        "Veterans Village (Project 7 / Muñoz)","West Triangle"
      ];
      foreach ($district1 as $b) echo "<option data-district='District 1'>$b</option>";
      ?>
    </optgroup>

    <!-- DISTRICT 3 FULL -->
    <optgroup label="District 3" data-group="District 3">
      <?php
      $district3 = [
       "Camp Aguinaldo","Pansol (Balara)","Mangga (Cubao)","San Roque (Cubao)","Silangan (Cubao)",
       "Socorro (Araneta City)","Bagumbayan (Eastwood)","Libis (Eastwood)",
       "Ugong Norte (Green Meadows / Corinthian / Ortigas)","Masagana (Jacobo Zobel)",
       "Loyola Heights (Katipunan)","Matandang Balara (Old Balara)","East Kamias (Project 1)",
       "Quirino 2-A (Project 2 / Anonas)","Quirino 2-B (Project 2 / Anonas)","Quirino 2-C (Project 2 / Anonas)",
       "Amihan (Project 3)","Claro (Quirino 3-B)","Duyan-duyan (Project 3)","Quirino 3-A (Project 3 / Anonas)",
       "Bagumbuhay (Project 4)","Bayanihan (Project 4)","Blue Ridge A (Project 4)","Blue Ridge B (Project 4)",
       "Dioquino Zobel (Project 4)","Escopa I","Escopa II","Escopa III","Escopa IV","Marilag (Project 4)",
       "Milagrosa (Project 4)","Tagumpay (Project 4)","Villa Maria Clara (Project 4)",
       "E. Rodriguez (Project 5 / Cubao)","West Kamias (Project 5 / Kamias)","St. Ignatius","White Plains"
      ];
      foreach ($district3 as $b) echo "<option data-district='District 3'>$b</option>";
      ?>
    </optgroup>

    <!-- DISTRICT 4 FULL -->
    <optgroup label="District 4" data-group="District 4">
      <?php
      $district4 = [
        "Bagong Lipunan ng Crame (Camp Crame)","Botocan (Diliman)","Central (Diliman)","Damayang Lagi (New Manila)",
        "Don Manuel (Galas)","Doña Aurora (Galas)","Doña Imelda (Sta. Mesa / Galas)","Doña Josefa (Galas)",
        "Horseshoe","Immaculate Concepcion (Cubao)","Kalusugan (St. Luke’s)","Kamuning","Kaunlaran (Cubao)",
        "Kristong Hari","Krus na Ligas (Diliman)","Laging Handa (Diliman)","Malaya (Diliman)",
        "Mariana (New Manila)","Obrero (Project 1)","Old Capitol Site (Diliman)","Paligsahan (Diliman)",
        "Pinagkaisahan (Cubao)","Pinyahan (Triangle Park)","Roxas (Project 1)","Sacred Heart (Kamuning)",
        "San Isidro Galas (Galas)","San Martin de Porres (Cubao)","San Vicente (Diliman)","Santol",
        "Sikatuna Village (Diliman)","South Triangle (Diliman)","Sto. Niño (Galas)","Tatalon",
        "Teacher's Village East (Diliman)","Teacher's Village West (Diliman)","U.P. Campus (Diliman)",
        "U.P. Village (Diliman)","Valencia (Gilmore / N. Domingo)"
      ];
      foreach ($district4 as $b) echo "<option data-district='District 4'>$b</option>";
      ?>
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

  <p style="text-align:center; margin-top:10px;">Already registered?
    <a href="skytrufiber.php">Login here</a>
  </p>

</form>

<script>
// Auto today's date
document.addEventListener("DOMContentLoaded", () => {
  const d = new Date();
  document.getElementById("date_installed").value =
    d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});

// SEARCH
document.getElementById("searchBar").addEventListener("keyup", function() {
  const filter = this.value.toLowerCase();
  const selectedDistrict = document.getElementById("district").value;
  const options = document.querySelectorAll("#location option");

  options.forEach(opt => {
    const matchDistrict = opt.getAttribute("data-district") === selectedDistrict;
    const searchMatch = opt.textContent.toLowerCase().includes(filter);
    opt.style.display = matchDistrict && searchMatch ? "" : "none";
  });
});

// AUTO FILTER WHEN DISTRICT CHANGES
document.getElementById("district").addEventListener("change", function () {
  const selectedDistrict = this.value;
  const options = document.querySelectorAll("#location option");
  const groups = document.querySelectorAll("#location optgroup");

  options.forEach(opt => {
    opt.style.display = opt.getAttribute("data-district") === selectedDistrict ? "" : "none";
  });

  groups.forEach(group => {
    group.style.display = group.getAttribute("data-group") === selectedDistrict ||
                          group.getAttribute("label").startsWith("⭐ Popular - " + selectedDistrict)
                          ? "" : "none";
  });

  document.getElementById("location").value = "";
});

// AUTO FILL DISTRICT BASED ON BARANGAY CLICKED
document.getElementById("location").addEventListener("change", function () {
  const selected = this.options[this.selectedIndex];
  const dist = selected.getAttribute("data-district");
  document.getElementById("district").value = dist;
});
</script>

</body>
</html>
