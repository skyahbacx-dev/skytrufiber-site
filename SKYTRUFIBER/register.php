<?php
// ALWAYS use an absolute include path
require_once __DIR__ . '/../db_connect.php';

// Get encrypted source passed from consent.php
$source = $_GET['source'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $account_number = trim($_POST['account_number']);
    $full_name      = trim($_POST['full_name']);
    $email          = trim($_POST['email']);
    $district       = trim($_POST['district']);
    $barangay       = trim($_POST['location']);
    $date_installed = trim($_POST['date_installed']);
    $remarks        = trim($_POST['remarks']);
    $password       = $account_number; 
    $source         = trim($_POST['source']); // from hidden input

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {

        try {
            $conn->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert user
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

            // Save optional feedback
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

            // ALWAYS redirect to encrypted SkyTruFiber login
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
/* GLOBAL LAYOUT */
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
    margin: 0;
    padding-top: 25px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* LOGO */
.logo-container img {
    width: 150px;
    border-radius: 50%;
}
@media (max-width: 600px) {
    .logo-container img { width: 115px; }
}

/* FORM BOX */
form {
    background: white;
    padding: 25px;
    border-radius: 15px;
    width: 420px;
    max-width: 92%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* INPUTS */
input, select, textarea {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
}

/* BUTTON */
button {
    width: 100%;
    padding: 12px;
    background: #0099cc;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 15px;
    font-size: 16px;
    font-weight: bold;
}
button:hover { background: #007a99; }

/* ERROR MESSAGE */
.message { text-align: center; color: red; margin-top: 10px; }

/* BARANGAY DROPDOWN */
.dropdown-panel {
    position: absolute;
    width: 100%;
    background: white;
    border-radius: 10px;
    border: 1px solid #ccc;
    margin-top: 3px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    z-index: 999;
}
.dropdown-item {
    padding: 12px;
    cursor: pointer;
}
.dropdown-item:hover { background: #e8f4ff; }
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

    <label>Feedback / Comments:</label>
    <textarea name="remarks"></textarea>

    <button type="submit">Submit</button>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <p style="text-align:center; margin-top:10px;">
        Already registered?  
        <a href="/fiber">Login here</a>
    </p>
</form>

<script>
/* SAME BARANGAY SEARCH SYSTEM YOU ALREADY HAVE */
const barangays = {
  "District 1": [...],
  "District 3": [...],
  "District 4": [...]
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

document.addEventListener("click", (e) => {
    if (!dropdownList.contains(e.target) && e.target !== barangaySearch) {
        dropdownList.style.display = "none";
    }
});

function updateDropdown() {
    const district = districtSelect.value;
    const text = barangaySearch.value.toLowerCase();

    dropdownList.innerHTML = "";

    if (!barangays[district]) {
        dropdownList.style.display = "none";
        return;
    }

    const filtered = barangays[district].filter(b => 
        b.toLowerCase().includes(text)
    );

    filtered.forEach(brgy => {
        let div = document.createElement("div");
        div.className = "dropdown-item";
        div.textContent = brgy;
        div.onclick = () => {
            barangaySearch.value = brgy;
            hiddenBarangay.value = brgy;
            dropdownList.style.display = "none";
        };
        dropdownList.appendChild(div);
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
