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

    if (
        preg_match("/^[0-9]{9,13}$/", $account_number) &&
        $full_name && $email && $district && $barangay && $date_installed
    ) {

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

            header("Location: /fiber/register.php?success=1");
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "❌ Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "⚠ Please fill out all required fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Registration – SkyTruFiber</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
    margin: 0;
    padding: 30px 0;
    display: flex;
    justify-content: center;
}

.form-container {
    background: #fff;
    width: 500px;
    max-width: 92%;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    text-align: center;
}

.logo-box {
    width: 150px;
    height: 150px;
    overflow: hidden;
    border-radius: 50%;
    margin: 0 auto 10px auto;
    border: 4px solid #0099cc;
    padding: 5px;
    background: white;
}

.logo-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

h2 {
    margin-bottom: 10px;
}

form label {
    display: block;
    text-align: left;
    font-weight: bold;
    margin-top: 12px;
    margin-bottom: 4px;
    color: #333;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
    transition: border 0.2s ease;
}

input:valid, textarea:valid {
    border-color: #27ae60;
}

input:invalid:focus {
    border-color: #e74c3c;
}

button {
    width: 100%;
    padding: 12px;
    background: #0099cc;
    color: white;
    border: none;
    border-radius: 10px;
    margin-top: 20px;
    font-size: 17px;
    font-weight: bold;
    cursor: pointer;
}
button:hover {
    background: #007a99;
}

.message {
    color: red;
    text-align: center;
}

.dropdown-wrapper { position: relative; }

.searchable-select {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    cursor: pointer;
}

.dropdown-list {
    position: absolute;
    width: 100%;
    background: white;
    border: 1px solid #ccc;
    border-radius: 10px;
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
.dropdown-item:hover {
    background: #e8f4ff;
}

.footer-text {
    margin-top: 15px;
    text-align: center;
}
</style>
</head>

<body>

<div class="form-container">

    <div class="logo-box">
        <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    </div>

    <h2>Customer Registration & Feedback</h2>

<form method="POST">

    <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">

    <label>Account Number:</label>
    <input type="text" name="account_number" required 
        minlength="9" maxlength="13" pattern="[0-9]+"
        placeholder="Enter 9–13 digit account number">

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
        <input type="text" id="barangaySelector" class="searchable-select" 
               placeholder="Search or select barangay..." autocomplete="off">
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

    <p class="footer-text">Already registered?  
        <a href="/fiber">Login here</a>
    </p>

</form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_GET["success"])): ?>
<script>
Swal.fire({
    title: "Registration Successful!",
    text: "Welcome to SkyTruFiber! Redirecting...",
    icon: "success",
    showConfirmButton: false,
    timer: 2500
}).then(() => {
    window.location.href = "/fiber";
});
</script>
<?php endif; ?>

<script>
/* Barangay lists */
const barangays = {
  "District 1": ["Alicia (Bago Bantay)","Bagong Pag-asa","Bahay Toro","Balingasa","Bungad","Damar","Damayan","Del Monte"],
  "District 3": ["Camp Aguinaldo","Pansol","San Roque","Silangan","Socorro","Bagumbayan","Libis"],
  "District 4": ["Bagong Lipunan","Botocan","Central","Damayang Lagi","Don Manuel","Horseshoe","Immaculate Concepcion"]
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

/* Auto fill today's date */
document.addEventListener("DOMContentLoaded", () => {
    const d = new Date();
    document.getElementById("date_installed").value =
        d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
