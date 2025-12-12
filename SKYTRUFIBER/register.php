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
        $message = "⚠ Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Registration – SkyTruFiber</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(to bottom right, #cceeff, #d9f4ff);
    margin: 0;
    padding-top: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* FORM CARD */
form {
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(10px);
    padding: 35px 30px;
    border-radius: 20px;
    width: 430px;
    max-width: 92%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    text-align: center;
    animation: fadeIn 0.6s ease;
}

/* LOGO DESIGN — centered + floating animation */
.logo-container {
    text-align: center;
    margin-bottom: 10px;
}

.logo-container img {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 5px 20px rgba(0,0,0,0.25);
    border: 3px solid white;
    animation: floatLogo 3s infinite ease-in-out;
}

@keyframes floatLogo {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* INPUTS */
input, select, textarea {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border-radius: 10px;
    border: 1px solid #b9cfe0;
    font-size: 15px;
    box-sizing: border-box;
}

/* SUBMIT BUTTON */
button {
    width: 100%;
    padding: 14px;
    background: #00a6d6;
    color: white;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    margin-top: 18px;
    font-size: 16px;
    font-weight: bold;
    transition: 0.25s ease;
}

button:hover {
    background: #008bb3;
    transform: translateY(-2px);
}

/* ERROR MESSAGE */
.message { color: red; text-align: center; }

/* LOGIN TEXT */
form p a {
    color: #006b9c;
    text-decoration: none;
}
form p a:hover { text-decoration: underline; }

/* BARANGAY DROPDOWN */
.dropdown-wrapper { position: relative; }
.searchable-select {
    cursor: pointer;
    background: white;
}
.dropdown-list {
    position: absolute;
    width: 100%;
    background: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    margin-top: 2px;
    max-height: 220px;
    overflow-y: auto;
    display: none;
    z-index: 9999;
}
.dropdown-item {
    padding: 10px;
    cursor: pointer;
}
.dropdown-item:hover { background: #e8f4ff; }
</style>
</head>

<body>

<form method="POST">

    <div class="logo-container">
        <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    </div>

    <h2>Customer Registration & Feedback</h2>

    <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">

    <label>Account Number:</label>
    <input type="text" 
           name="account_number"
           required 
           maxlength="13"
           minlength="9"
           pattern="[0-9]{9,13}"
           placeholder="Enter 9–13 digit account number"
           oninput="this.value=this.value.replace(/[^0-9]/g,'');">

    <label>Full Name:</label>
    <input type="text" name="full_name" required placeholder="Enter full name">

    <label>Email:</label>
    <input type="email" name="email" required placeholder="example@email.com">

    <label>District:</label>
    <select id="district" name="district" required>
        <option value="">Select District</option>
        <option value="District 1">District 1</option>
        <option value="District 3">District 3</option>
        <option value="District 4">District 4</option>
    </select>

    <label>Barangay:</label>
    <div class="dropdown-wrapper">
        <input type="text" id="barangaySelector" class="searchable-select" placeholder="Search or select barangay..." autocomplete="off">
        <input type="hidden" id="location" name="location" required>
        <div id="dropdownList" class="dropdown-list"></div>
    </div>

    <label>Date Installed:</label>
    <input type="date" id="date_installed" name="date_installed" required>

    <label>Feedback / Comments (Optional):</label>
    <textarea name="remarks" placeholder="Your feedback helps us improve"></textarea>

    <button type="submit">Submit</button>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <p>Already registered? <a href="/fiber">Login here</a></p>
</form>

<script>
/* FULL BARANGAY DATA */
const barangays = {
  "District 1": [
"Alicia (Bago Bantay)","Bagong Pag-asa","Bahay Toro","Balingasa","Bungad","Damar","Damayan",
"Del Monte","Katipunan","Lourdes","Maharlika","Manresa","Mariblo","Masambong",
"N.S. Amoranto","Nayong Kanluran","Paang Bundok","Pag-ibig sa Nayon","Paltok","Paraiso",
"Phil-Am","Project 6","Ramon Magsaysay","Saint Peter","Salvacion","San Antonio",
"San Isidro Labrador","San Jose","Santa Cruz","Santa Teresita","Santo Domingo","Siena",
"Sto. Cristo","Talayan","Vasra","Veterans Village","West Triangle"
  ],
  "District 3": [
"Camp Aguinaldo","Pansol","Mangga","San Roque","Silangan","Socorro","Bagumbayan","Libis","Ugong Norte",
"Masagana","Loyola Heights","Matandang Balara","East Kamias","Quirino 2-A","Quirino 2-B","Quirino 2-C",
"Amihan","Claro","Duyan-duyan","Quirino 3-A","Bagumbuhay","Bayanihan","Blue Ridge A","Blue Ridge B",
"Dioquino Zobel","Escopa I","Escopa II","Escopa III","Escopa IV","Marilag","Milagrosa","Tagumpay",
"Villa Maria Clara","E. Rodriguez","West Kamias","St. Ignatius","White Plains"
  ],
  "District 4": [
"Bagong Lipunan ng Crame","Botocan","Central","Damayang Lagi","Don Manuel","Doña Aurora","Doña Imelda",
"Doña Josefa","Horseshoe","Immaculate Concepcion","Kalusugan","Kamuning","Kaunlaran","Kristong Hari",
"Krus na Ligas","Laging Handa","Malaya","Mariana","Obrero","Old Capitol Site","Paligsahan",
"Pinagkaisahan","Pinyahan","Roxas","Sacred Heart","San Isidro Galas","San Martin de Porres",
"San Vicente","Santol","Sikatuna Village","South Triangle","Sto. Niño","Tatalon",
"Teacher's Village East","Teacher's Village West","U.P. Campus","U.P. Village","Valencia"
  ]
};

const districtSelect = document.getElementById('district');
const searchInput = document.getElementById('barangaySelector');
const dropdownList = document.getElementById('dropdownList');
const hiddenBarangay = document.getElementById('location');

searchInput.addEventListener("focus", () => {
    populateDropdown("");
    dropdownList.style.display = "block";
});

searchInput.addEventListener("input", () => {
    populateDropdown(searchInput.value.toLowerCase());
    dropdownList.style.display = "block";
});

document.addEventListener("click", (e) => {
    if (!dropdownList.contains(e.target) && e.target !== searchInput) {
        dropdownList.style.display = "none";
    }
});

function populateDropdown(filter) {
    dropdownList.innerHTML = "";
    const district = districtSelect.value;

    if (!barangays[district]) return;

    barangays[district]
        .filter(b => b.toLowerCase().includes(filter))
        .forEach(brgy => {
            let div = document.createElement("div");
            div.className = "dropdown-item";
            div.textContent = brgy;

            div.onclick = () => {
                searchInput.value = brgy;
                hiddenBarangay.value = brgy;
                dropdownList.style.display = "none";
            };

            dropdownList.appendChild(div);
        });
}

/* Auto-fill date */
document.addEventListener("DOMContentLoaded", () => {
    const d = new Date();
    document.getElementById("date_installed").value =
        d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
