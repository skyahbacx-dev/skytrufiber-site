<?php
require_once __DIR__ . '/../db_connect.php';

$message = '';
$source = $_GET['source'] ?? '';

/* Show success modal if ok=registered */
$justRegistered = isset($_GET['ok']) && $_GET['ok'] === 'registered';

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

            // 🔐 PREVENT DUPLICATES
            $check = $conn->prepare("
                SELECT id FROM users 
                WHERE account_number = :acc 
                OR email = :email
                OR full_name = :name
                LIMIT 1
            ");
            $check->execute([
                ':acc'   => $account_number,
                ':email' => $email,
                ':name'  => $full_name
            ]);

            if ($check->fetch()) {
                $message = "⚠ This account number, email, or name is already registered.";
            } else {

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

                header("Location: /fiber/register?ok=registered");
                exit;
            }

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
    font-family: Arial, sans-serif;
    background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
    margin: 0;
    padding-top: 25px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Form container */
form {
    background: #fff;
    padding: 35px;
    border-radius: 20px;
    width: 450px;
    max-width: 92%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    position: relative;
}

/* Logo inside the form container */
.logo-inside {
    display: flex;
    justify-content: center;
    margin-bottom: 10px;
}
.logo-inside img {
    width: 140px;
    border-radius: 50%;
    background: white;
    padding: 10px;
    border: 3px solid #0099cc;
}

h2 {
    text-align: center;
    margin-bottom: 15px;
}

/* Labels aligned left */
label {
    display: block;
    margin-top: 12px;
    margin-bottom: 3px;
    font-weight: bold;
}

/* Inputs */
input, select, textarea {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
}

/* Acc number strict formatting */
input[name='account_number'] {
    letter-spacing: 1px;
}

button {
    width: 100%;
    padding: 12px;
    background: #0099cc;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    margin-top: 20px;
    font-size: 17px;
    font-weight: bold;
}
button:hover { background: #007a99; }

.message { color: red; text-align: center; margin-top: 10px; }

/* Dropdown */
.dropdown-wrapper { position: relative; }
.searchable-select {
    cursor: pointer;
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
    z-index: 999;
}
.dropdown-item {
    padding: 10px;
    cursor: pointer;
}
.dropdown-item:hover {
    background: #e8f4ff;
}

</style>
</head>

<body>

<form method="POST">

    <div class="logo-inside">
        <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    </div>

    <h2>Customer Registration & Feedback</h2>

    <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">

    <label>Account Number:</label>
    <input type="text" name="account_number" minlength="9" maxlength="13" 
           pattern="[0-9]{9,13}" placeholder="Enter 9–13 digit account number" required>

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

    <p style="text-align:center;">Already registered?
        <a href="/fiber">Login here</a>
    </p>
</form>

<script>
/* ---------- SWEETALERT SUCCESS POPUP ---------- */
<?php if ($justRegistered): ?>
Swal.fire({
    title: "Registration Successful!",
    text: "Thank you! You may now log in to your SkyTruFiber account.",
    icon: "success",
    confirmButtonColor: "#0099cc"
});
/* Remove token from URL so popup doesn't repeat */
history.replaceState({}, document.title, "/fiber/register");
<?php endif; ?>
</script>


<script>
/* ---------- BARANGAY SEARCH SYSTEM ---------- */
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

const districtSelect = document.getElementById("district");
const searchInput = document.getElementById("barangaySelector");
const dropdownList = document.getElementById("dropdownList");
const hiddenBarangay = document.getElementById("location");

/* Populate dropdown */
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

/* Show full dropdown on click */
searchInput.addEventListener("focus", () => {
    populateDropdown("");
    dropdownList.style.display = "block";
});

/* Search filter */
searchInput.addEventListener("input", () => {
    populateDropdown(searchInput.value.toLowerCase());
    dropdownList.style.display = "block";
});

/* Hide when clicking outside */
document.addEventListener("click", (e) => {
    if (!dropdownList.contains(e.target) && e.target !== searchInput) {
        dropdownList.style.display = "none";
    }
});

/* Auto-fill date */
document.addEventListener("DOMContentLoaded", () => {
    const d = new Date();
    document.getElementById("date_installed").value =
        d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
