<?php
require_once __DIR__ . '/../db_connect.php';

$message = '';
$source = $_GET['source'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $account_number = trim($_POST['account_number']);
    $full_name      = trim($_POST['full_name']);
    $email          = trim($_POST['email']);
    $district       = trim($_POST['district']);
    $barangay       = trim($_POST['location']);
    $date_installed = trim($_POST['date_installed']);
    $remarks        = trim($_POST['remarks']);
    $password       = $account_number;
    $source         = trim($_POST['source']);

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {

        try {
            $conn->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("
                INSERT INTO users 
                (account_number, full_name, email, password, district, barangay, date_installed, privacy_consent, source, created_at)
                VALUES 
                (:acc, :name, :email, :pw, :district, :barangay, :installed, 'yes', :source, NOW())
            ");

            $stmt->execute([
                ':acc'      => $account_number,
                ':name'     => $full_name,
                ':email'    => $email,
                ':pw'       => $hash,
                ':district' => $district,
                ':barangay' => $barangay,
                ':installed'=> $date_installed,
                ':source'   => $source
            ]);

            if ($remarks) {
                $stmt2 = $conn->prepare("
                    INSERT INTO survey_responses 
                    (client_name, account_number, district, location, feedback, source, created_at)
                    VALUES 
                    (:name, :acc, :district, :barangay, :feedback, :source, NOW())
                ");
                $stmt2->execute([
                    ':name'     => $full_name,
                    ':acc'      => $account_number,
                    ':district' => $district,
                    ':barangay' => $barangay,
                    ':feedback' => $remarks,
                    ':source'   => $source
                ]);
            }

            $conn->commit();
            header("Location: /fiber?msg=success");
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "❌ Database error: " . htmlspecialchars($e->getMessage());
        }

    } else {
        $message = "⚠️ Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Registration – SkyTruFiber</title>

<style>
/* same CSS you had */
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
        <input id="barangaySearch" type="text" placeholder="Search barangay..." autocomplete="off">
        <input type="hidden" id="location" name="location" required>
        <div id="dropdownList" class="dropdown-panel"></div>
    </div>

    <label>Date Installed:</label>
    <input type="date" id="date_installed" name="date_installed" required>

    <label>Feedback / Comments (Optional):</label>
    <textarea name="remarks"></textarea>

    <button type="submit">Submit</button>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <p style="text-align:center; margin-top:10px;">
        Already registered? <a href="/fiber">Login here</a>
    </p>
</form>

<script>
// FULL BARANGAY LIST RESTORED (WORKING)
const barangays = {
  "District 1": [
"Alicia (Bago Bantay)","Bagong Pag-asa","Bahay Toro","Balingasa",
"Bungad","Damar","Damayan","Del Monte","Katipunan","Lourdes",
"Maharlika","Manresa","Mariblo","Masambong","N.S. Amoranto",
"Nayong Kanluran","Paang Bundok","Pag-ibig sa Nayon","Paltok",
"Paraiso","Phil-Am","Project 6","Ramon Magsaysay","Saint Peter",
"Salvacion","San Antonio","San Isidro Labrador","San Jose",
"Santa Cruz","Santa Teresita","Santo Domingo","Siena",
"Sto. Cristo","Talayan","Vasra","Veterans Village","West Triangle"
  ],
  "District 3": [
"Camp Aguinaldo","Pansol","Mangga","San Roque","Silangan","Socorro",
"Bagumbayan","Libis","Ugong Norte","Masagana","Loyola Heights",
"Matandang Balara","East Kamias","Quirino 2-A","Quirino 2-B","Quirino 2-C",
"Amihan","Claro","Duyan-duyan","Quirino 3-A","Bagumbuhay","Bayanihan",
"Blue Ridge A","Blue Ridge B","Dioquino Zobel","Escopa I","Escopa II",
"Escopa III","Escopa IV","Marilag","Milagrosa","Tagumpay",
"Villa Maria Clara","E. Rodriguez","West Kamias","St. Ignatius",
"White Plains"
  ],
  "District 4": [
"Bagong Lipunan ng Crame","Botocan","Central","Damayang Lagi",
"Don Manuel","Doña Aurora","Doña Imelda","Doña Josefa","Horseshoe",
"Immaculate Concepcion","Kalusugan","Kamuning","Kaunlaran","Kristong Hari",
"Krus na Ligas","Laging Handa","Malaya","Mariana","Obrero",
"Old Capitol Site","Paligsahan","Pinagkaisahan","Pinyahan","Roxas",
"Sacred Heart","San Isidro Galas","San Martin de Porres","San Vicente",
"Santol","Sikatuna Village","South Triangle","Sto. Niño","Tatalon",
"Teacher's Village East","Teacher's Village West","U.P. Campus",
"U.P. Village","Valencia"
  ]
};

const districtSelect = document.getElementById('district');
const barangaySearch = document.getElementById('barangaySearch');
const dropdownList = document.getElementById('dropdownList');
const hiddenBarangay = document.getElementById('location');

districtSelect.addEventListener('change', () => {
    barangaySearch.value = "";
    hiddenBarangay.value = "";
    dropdownList.style.display = "none";
});

barangaySearch.addEventListener('input', updateDropdown);

document.addEventListener("click", e => {
    if (!dropdownList.contains(e.target) && e.target !== barangaySearch) {
        dropdownList.style.display = "none";
    }
});

function updateDropdown() {
    const district = districtSelect.value;
    const text = barangaySearch.value.toLowerCase();

    dropdownList.innerHTML = "";

    if (!barangays[district]) return;

    const filtered = barangays[district].filter(b =>
        b.toLowerCase().includes(text)
    );

    filtered.forEach(brgy => {
        const item = document.createElement("div");
        item.className = "dropdown-item";
        item.textContent = brgy;
        item.onclick = () => {
            barangaySearch.value = brgy;
            hiddenBarangay.value = brgy;
            dropdownList.style.display = "none";
        };
        dropdownList.appendChild(item);
    });

    dropdownList.style.display = "block";
}

document.addEventListener("DOMContentLoaded", () => {
    const d = new Date();
    document.getElementById("date_installed").value =
        d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
