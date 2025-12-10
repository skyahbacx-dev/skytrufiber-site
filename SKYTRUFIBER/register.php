<?php
include '../db_connect.php';

$message = '';
$source = $_GET['source'] ?? ''; // Receive source from consent page

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $account_number = trim($_POST['account_number']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $district = trim($_POST['district']);
    $barangay = trim($_POST['location']);
    $date_installed = trim($_POST['date_installed']);
    $remarks = trim($_POST['remarks']);
    $password = $account_number;
    $source = trim($_POST['source']);

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {

        try {
            $conn->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("
                INSERT INTO users (account_number, full_name, email, password, district, barangay, date_installed, privacy_consent, source, created_at)
                VALUES (:acc, :name, :email, :pw, :district, :brgy, :installed, 'yes', :source, NOW())
            ");
            $stmt->execute([
                ':acc' => $account_number,
                ':name' => $full_name,
                ':email' => $email,
                ':pw' => $hash,
                ':district' => $district,
                ':brgy' => $barangay,
                ':installed' => $date_installed,
                ':source' => $source
            ]);

            if ($remarks) {
                $stmt2 = $conn->prepare("
                    INSERT INTO survey_responses (client_name, account_number, district, location, feedback, source, created_at)
                    VALUES (:name, :acc, :district, :brgy, :feedback, :source, NOW())
                ");
                $stmt2->execute([
                    ':name' => $full_name,
                    ':acc' => $account_number,
                    ':district' => $district,
                    ':brgy' => $barangay,
                    ':feedback' => $remarks,
                    ':source' => $source
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
/* ------------------ GLOBAL ------------------ */

body {
  font-family: Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display:flex; flex-direction:column; align-items:center; justify-content:flex-start;
  min-height:100vh; margin:0; padding-top:30px;
}

form {
  background:#fff; 
  padding:25px; 
  border-radius:15px; 
  width:420px;
  max-width:92%;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
}

h2 {
  text-align:center; 
  color:#004466; 
  margin-bottom:15px;
}

label {
  font-weight:600; 
  color:#004466; 
  display:block; 
  margin-top:12px;
}

input, select, textarea {
  width:100%; 
  padding:12px; 
  margin-top:6px; 
  border-radius:8px; 
  border:1px solid #ccc;
  font-size:15px;
}

/* ------------------ BARANGAY DROPDOWN ------------------ */

.search-bar {
  width:100%; 
  padding:12px; 
  border-radius:8px; 
  border:1px solid #aaa;
  margin-top:6px;
}

.dropdown-panel {
  position:absolute;
  width:100%;
  background:white;
  border-radius:10px;
  border:1px solid #ccc;
  margin-top:3px;
  max-height:200px;
  overflow-y:auto;
  display:none;
  z-index:999;
  box-shadow:0 4px 10px rgba(0,0,0,0.15);
}

.dropdown-item {
  padding:12px;
  cursor:pointer;
  transition:0.15s;
  font-size:15px;
}

.dropdown-item:hover {
  background:#e8f4ff;
}

.no-results {
  padding:12px;
  color:#777;
  font-style:italic;
}

/* ------------------ BUTTON ------------------ */

button {
  width:100%; 
  padding:12px; 
  background:#0099cc; 
  color:white; 
  border:none;
  border-radius:8px; 
  cursor:pointer; 
  margin-top:18px; 
  font-weight:bold;
  font-size:16px;
}

button:hover {
  background:#007a99;
}

/* ------------------ IMAGE ------------------ */

.logo-container img {
  width:140px;
  border-radius:50%;
  margin-bottom:15px;
  transition:0.3s;
}

/* ------------------ MEDIA QUERIES ------------------ */

/* Tablets (iPad) */
@media (max-width: 900px) {
  form { width:80%; }
}

/* Mobile Phones */
@media (max-width: 600px) {

  body {
    padding-top:20px;
  }

  .logo-container img {
    width:110px;
  }

  form {
    width:90%;
    padding:18px;
  }

  input, select, textarea, .search-bar {
    padding:14px;
    font-size:16px;
  }

  .dropdown-item {
    padding:14px;
    font-size:16px;
  }

  button {
    padding:14px;
    font-size:18px;
  }
}
</style>
</head>

<body>

<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Registration & Feedback</h2>

  <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">

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
  <div style="position:relative;">
      <input id="barangaySearch" class="search-bar" type="text" placeholder="Search barangay..." autocomplete="off">
      <input type="hidden" id="location" name="location" required>
      <div id="dropdownList" class="dropdown-panel"></div>
  </div>

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
const barangays = {
  "District 1": [
"Alicia (Bago Bantay)","Bagong Pag-asa (North EDSA / Triangle Park)","Bahay Toro (Project 8)","Balingasa (Balintawak / Cloverleaf)",
"Bungad (Project 7)","Damar","Damayan (San Francisco del Monte / Frisco)","Del Monte (San Francisco del Monte / Frisco)",
"Katipunan (Muñoz)","Lourdes (Sta. Mesa Heights)","Maharlika (Sta. Mesa Heights)","Manresa","Mariblo (SFDM / Frisco)","Masambong",
"N.S. Amoranto (Gintong Silahis, La Loma)","Nayong Kanluran","Paang Bundok (La Loma)","Pag-ibig sa Nayon (Balintawak)","Paltok (SFDM / Frisco)",
"Paraiso (SFDM / Frisco)","Phil-Am (West Triangle)","Project 6 (Diliman / Triangle Park)","Ramon Magsaysay (Bago Bantay)",
"Saint Peter (Sta. Mesa Heights)","Salvacion (La Loma)","San Antonio (SFDM / Frisco)","San Isidro Labrador (La Loma)","San Jose (La Loma)",
"Santa Cruz (Pantranco / Heroes Hill)","Santa Teresita (Sta. Mesa Heights)","Santo Domingo (Matalahib)","Siena","Sto. Cristo (Bago Bantay)",
"Talayan","Vasra (Diliman)","Veterans Village (Project 7 / Muñoz)","West Triangle"
  ],
  "District 3": [
"Camp Aguinaldo","Pansol (Balara)","Mangga (Cubao)","San Roque (Cubao)","Silangan (Cubao)","Socorro (Araneta City)","Bagumbayan (Eastwood)",
"Libis (Eastwood)","Ugong Norte (Green Meadows / Corinthian / Ortigas)","Masagana (Jacobo Zobel)","Loyola Heights (Katipunan)",
"Matandang Balara (Old Balara)","East Kamias (Project 1)","Quirino 2-A (Project 2 / Anonas)","Quirino 2-B (Project 2 / Anonas)",
"Quirino 2-C (Project 2 / Anonas)","Amihan (Project 3)","Claro (Quirino 3-B)","Duyan-duyan (Project 3)","Quirino 3-A (Project 3 / Anonas)",
"Bagumbuhay (Project 4)","Bayanihan (Project 4)","Blue Ridge A (Project 4)","Blue Ridge B (Project 4)","Dioquino Zobel (Project 4)",
"Escopa I","Escopa II","Escopa III","Escopa IV","Marilag (Project 4)","Milagrosa (Project 4)","Tagumpay (Project 4)",
"Villa Maria Clara (Project 4)","E. Rodriguez (Project 5 / Cubao)","West Kamias (Project 5 / Kamias)","St. Ignatius","White Plains"
  ],
  "District 4": [
"Bagong Lipunan ng Crame (Camp Crame)","Botocan (Diliman)","Central (Diliman)","Damayang Lagi (New Manila)","Don Manuel (Galas)",
"Doña Aurora (Galas)","Doña Imelda (Sta. Mesa / Galas)","Doña Josefa (Galas)","Horseshoe","Immaculate Concepcion (Cubao)",
"Kalusugan (St. Luke’s)","Kamuning","Kaunlaran (Cubao)","Kristong Hari","Krus na Ligas (Diliman)","Laging Handa (Diliman)",
"Malaya (Diliman)","Mariana (New Manila)","Obrero (Project 1)","Old Capitol Site (Diliman)","Paligsahan (Diliman)","Pinagkaisahan (Cubao)",
"Pinyahan (Triangle Park)","Roxas (Project 1)","Sacred Heart (Kamuning)","San Isidro Galas (Galas)","San Martin de Porres (Cubao)",
"San Vicente (Diliman)","Santol","Sikatuna Village (Diliman)","South Triangle (Diliman)","Sto. Niño (Galas)","Tatalon",
"Teacher's Village East (Diliman)","Teacher's Village West (Diliman)","U.P. Campus (Diliman)","U.P. Village (Diliman)",
"Valencia (Gilmore / N. Domingo)"
  ]
};

const districtSelect = document.getElementById('district');
const barangaySearch = document.getElementById('barangaySearch');
const dropdownList = document.getElementById('dropdownList');
const hiddenBarangay = document.getElementById('location');

// Reset on district change
districtSelect.addEventListener('change', () => {
    barangaySearch.value = "";
    hiddenBarangay.value = "";
    dropdownList.style.display = "none";
});

// Filter during typing
barangaySearch.addEventListener('input', () => updateDropdown());

// Hide dropdown when clicking outside
document.addEventListener("click", (event) => {
    if (!dropdownList.contains(event.target) && event.target !== barangaySearch) {
        dropdownList.style.display = "none";
    }
});

function updateDropdown() {
    const district = districtSelect.value;
    const searchText = barangaySearch.value.toLowerCase();

    dropdownList.innerHTML = "";

    if (!barangays[district]) {
        dropdownList.style.display = "none";
        return;
    }

    const filtered = barangays[district].filter(b =>
        b.toLowerCase().includes(searchText)
    );

    if (filtered.length === 0) {
        dropdownList.innerHTML = `<div class='no-results'>No results found</div>`;
        dropdownList.style.display = "block";
        return;
    }

    filtered.forEach(b => {
        const div = document.createElement("div");
        div.className = "dropdown-item";
        div.textContent = b;

        div.addEventListener("click", () => {
            barangaySearch.value = b;
            hiddenBarangay.value = b;
            dropdownList.style.display = "none";
        });

        dropdownList.appendChild(div);
    });

    dropdownList.style.display = "block";
}

// Auto-fill today's date
document.addEventListener("DOMContentLoaded", () => {
    const d = new Date();
    document.getElementById("date_installed").value =
        d.getFullYear() + "-" + 
        String(d.getMonth()+1).padStart(2, "0") + "-" + 
        String(d.getDate()).padStart(2, "0");
});
</script>

</body>
</html>
