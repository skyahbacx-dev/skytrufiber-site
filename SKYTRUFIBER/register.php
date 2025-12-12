<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$message = '';
$success = false;

/* ============================================================
   🔐 VALIDATE TOKEN FROM index.php
============================================================ */
$token = $_GET["rt"] ?? ($_POST["rt"] ?? "");

if (!isset($_SESSION["registration_token"]) || $token !== $_SESSION["registration_token"]) {
    die("<h2 style='text-align:center;color:red'>❌ Invalid or expired registration link.</h2>");
}

/* ============================================================
   🚀 REGISTRATION HANDLER
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $account_number = trim($_POST['account_number']);
    $full_name      = trim($_POST['full_name']);
    $email          = trim($_POST['email']);
    $district       = trim($_POST['district']);
    $barangay       = trim($_POST['location']);
    $date_installed = trim($_POST['date_installed']);
    $remarks        = trim($_POST['remarks']);
    $source         = trim($_POST['source']);
    $password       = $account_number; // auto-initial password

    if ($account_number && $full_name && $email && $district && $barangay && $date_installed) {

        try {

            /* ============================================================
               ❗ DUPLICATE CHECK (email OR account number)
            ============================================================ */
            $check = $conn->prepare("
                SELECT id FROM users 
                WHERE email = :email OR account_number = :acc
                LIMIT 1
            ");
            $check->execute([
                ":email" => $email,
                ":acc"   => $account_number
            ]);

            if ($check->fetch()) {
                $message = "⚠ Email or Account Number is already registered.";
            } else {

                /* ============================================================
                   INSERT USER
                ============================================================ */
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

                /* Optional remarks insert */
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

                $success = true;

                // remove token so it cannot be reused
                unset($_SESSION["registration_token"]);
            }

        } catch (PDOException $e) {
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
    background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
    margin: 0;
    padding-top: 25px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

form.container {
    background: #fff;
    padding: 30px;
    border-radius: 18px;
    width: 430px;
    max-width: 92%;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    position: relative;
}

.logo-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-bottom: 12px;
}

.logo-wrapper img {
    width: 120px;
    border-radius: 50%;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

h2 {
    text-align: center;
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-top: 12px;
    text-align: left; /* FIX: no more centered labels */
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
}

button {
    width: 100%;
    padding: 12px;
    background: #0099cc;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 20px;
    font-size: 16px;
    font-weight: bold;
}
button:hover { background: #007a99; }

.message { color: red; text-align: center; }

.success-popup {
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.7);
    padding: 25px 35px;
    background: #00c851;
    color: white;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    font-size: 22px;
    text-align: center;
    opacity: 0;
    transition: 0.3s ease;
    z-index: 999999;
}

.success-popup.show {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}
</style>

</head>

<body>

<?php if ($success): ?>
<div id="successPopup" class="success-popup">
    ✅ Registration Successful!<br>Redirecting...
</div>

<script>
setTimeout(() => {
    document.getElementById("successPopup").classList.add("show");
}, 200);

setTimeout(() => {
    window.location.href = "/fiber";
}, 2200);
</script>

<?php endif; ?>

<form class="container" method="POST">

<div class="logo-wrapper">
    <img src="../SKYTRUFIBER.png" alt="Logo">
</div>

<h2>Customer Registration</h2>

<input type="hidden" name="rt" value="<?= htmlspecialchars($token) ?>">
<input type="hidden" name="source" value="<?= htmlspecialchars($_GET["source"] ?? "") ?>">

<label>Account Number:</label>
<input type="text" name="account_number" required 
       minlength="9" maxlength="13"
       pattern="[0-9]+"
       placeholder="Enter 9–13 digit account number">

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
<input type="text" name="location" id="location" required placeholder="Enter Barangay">

<label>Date Installed:</label>
<input type="date" id="date_installed" name="date_installed" required>

<label>Feedback / Comments (Optional):</label>
<textarea name="remarks"></textarea>

<button type="submit">Submit</button>

<?php if ($message): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<p style="text-align:center;">Already registered?
    <a href="/fiber">Login here</a>
</p>

</form>

<script>
// Auto-fill today's date
document.addEventListener("DOMContentLoaded", () => {
    const d = new Date();
    document.getElementById("date_installed").value =
        d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");
});
</script>

</body>
</html>
