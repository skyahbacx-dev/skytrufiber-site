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
/* ============================
   GLOBAL PAGE DESIGN
============================ */
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: linear-gradient(135deg, #cceeff, #e8f8ff);
    margin: 0;
    padding-top: 20px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

/* ============================
   GLASS CARD CONTAINER
============================ */
form {
    width: 450px;
    max-width: 92%;
    background: rgba(255, 255, 255, 0.70);
    backdrop-filter: blur(16px);
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity:0; transform: translateY(15px); }
    to { opacity:1; transform: translateY(0); }
}

.logo-container img {
    width: 160px;
    border-radius: 50%;
    display: block;
    margin: 0 auto 15px;
}

h2 {
    text-align: center;
    font-weight: 700;
    margin-top: 5px;
    margin-bottom: 18px;
}

/* ============================
   INPUTS / SELECTS / TEXTAREA
============================ */
input, select, textarea {
    width: 100%;
    padding: 13px;
    margin: 10px 0 18px 0;
    border-radius: 10px;
    border: 1px solid #aac7d8;
    font-size: 15px;
    box-sizing: border-box;
    background: #f9fcff;
    transition: all .2s;
}

input:focus, select:focus, textarea:focus {
    border-color: #0099cc;
    box-shadow: 0 0 6px #9bdaf0;
}

/* Barangay dropdown box */
.dropdown-wrapper {
    position: relative;
}

/* Autocomplete box */
.dropdown-list {
    position: absolute;
    width: 100%;
    background: white;
    border: 1px solid #aaa;
    border-radius: 10px;
    margin-top: -10px;
    max-height: 230px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
}

.dropdown-item {
    padding: 12px;
    cursor: pointer;
}
.dropdown-item:hover {
    background: #e4f5ff;
}

/* ============================
   SUBMIT BUTTON
============================ */
button {
    width: 100%;
    padding: 14px;
    background: #00a6cc;
    color: white;
    border: none;
    border-radius: 40px;
    font-size: 17px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.25s;
}
button:hover {
    background: #008db0;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

/* ============================
   FOOTER LINK
============================ */
p.footer {
    text-align: center;
    margin-top: 12px;
}
p.footer a {
    color: #006b9a;
    font-weight: bold;
    text-decoration: none;
}
p.footer a:hover {
    text-decoration: underline;
}

/* Error message */
.message {
    color: red;
    text-align: center;
    margin-top: -5px;
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
  <input type="text"
         name="account_number"
         id="account_number"
         required
         minlength="9"
         maxlength="13"
         inputmode="numeric"
         pattern="[0-9]{9,13}"
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
      <input type="text" id="barangaySelector" placeholder="Search or select barangay..." autocomplete="off">
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

  <p class="footer">Already registered?  
      <a href="/fiber">Login here</a>
  </p>
</form>

<script>
/* ACCOUNT NUMBER SAFETY FILTER */
document.getElementById("account_number").addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, "").slice(0, 13);
});

/* BARANGAY DATA (same list you already use) */
const barangays = {
  "District 1": [...],
  "District 3": [...],
  "District 4": [...]
};

const districtSelect = document.getElementById('district');
const searchInput = document.getElementById('barangaySelector');
const dropdownList = document.getElementById('dropdownList');
const hiddenBarangay = document.getElementById('location');

/* Populate barangays */
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

/* Auto-date install */
document.addEventListener("DOMContentLoaded", () => {
    const d = new Date();
    document.getElementById("date_installed").value =
        d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
