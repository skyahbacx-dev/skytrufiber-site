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
            header("Location: /fiber/register?success=1");
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

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
    margin: 0;
    padding: 25px 0;
    display: flex;
    justify-content: center;
}

.form-container {
    background: #fff;
    width: 500px;
    max-width: 92%;
    border-radius: 18px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    padding: 35px;
    text-align: left;
    position: relative;
}

.logo {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.logo img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0px 4px 12px rgba(0,0,0,0.25);
    border: 4px solid white;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

label {
    font-weight: bold;
    margin-top: 12px;
    display: block;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    margin-top: 4px;
    border-radius: 10px;
    border: 1px solid #bbb;
    font-size: 15px;
}

textarea {
    height: 80px;
    resize: none;
}

button {
    width: 100%;
    padding: 12px;
    background: #0099cc;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 18px;
    font-size: 17px;
    font-weight: bold;
}
button:hover { background: #007a99; }

.message { color: red; text-align: center; }

/* Barangay dropdown */
.dropdown-wrapper {
    position: relative;
}

.searchable-select {
    width: 100%;
}

.dropdown-list {
    position: absolute;
    width: 100%;
    background: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    max-height: 220px;
    overflow-y: auto;
    display: none;
    z-index: 9999;
}

.dropdown-item {
    padding: 10px;
    cursor: pointer;
}
.dropdown-item:hover {
    background: #e8f4ff;
}

.footer {
    margin-top: 12px;
    text-align: center;
}
.footer a {
    color: #0077a3;
    font-weight: bold;
}
</style>
</head>

<body>

<?php if (isset($_GET['success'])): ?>
<script>
Swal.fire({
    title: "Registration Successful!",
    text: "Redirecting to Login…",
    icon: "success",
    showConfirmButton: false,
    timer: 1800
});
setTimeout(() => {
    window.location.href = "/fiber";
}, 1800);
</script>
<?php endif; ?>

<div class="form-container">

    <div class="logo">
        <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    </div>

    <h2>Customer Registration & Feedback</h2>

<form method="POST">

    <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">

    <label>Account Number:</label>
    <input type="text" 
           name="account_number"
           placeholder="Enter 9–13 digit account number"
           minlength="9" maxlength="13"
           inputmode="numeric"
           pattern="[0-9]+"
           required>

    <label>Full Name:</label>
    <input type="text" name="full_name" placeholder="Enter full name" required>

    <label>Email:</label>
    <input type="email" name="email" placeholder="example@email.com" required>

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

    <p class="footer">Already registered? <a href="/fiber">Login here</a></p>

</form>
</div>

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

/* Show dropdown */
searchInput.addEventListener("focus", () => {
    populateDropdown("");
    dropdownList.style.display = "block";
});

/* Search filter */
searchInput.addEventListener("input", () => {
    populateDropdown(searchInput.value.toLowerCase());
    dropdownList.style.display = "block";
});

/* Close dropdown when clicking outside */
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
