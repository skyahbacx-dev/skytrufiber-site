<?php
session_start();
include '../db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $concern = trim($_POST['concern'] ?? '');

    if ($email_or_name && $password) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :input OR full_name = :input LIMIT 1");
            $stmt->execute([':input' => $email_or_name]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                $_SESSION['user'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];

                $clientStmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
                $clientStmt->execute([':name' => $user['full_name']]);
                $client = $clientStmt->fetch(PDO::FETCH_ASSOC);

                if (!$client) {
                    $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at) VALUES (:n, 'Unassigned', NOW())")
                         ->execute([':n' => $user['full_name']]);
                    $client_id = $conn->lastInsertId();
                } else {
                    $client_id = $client['id'];
                }

                if (!empty($concern)) {
                    $conn->prepare("INSERT INTO chat (client_id, sender_type, message, created_at)
                                    VALUES (:cid, 'client', :msg, NOW())")
                         ->execute([':cid' => $client_id, ':msg' => $concern]);
                }

                header("Location: chat_support.php?username=" . urlencode($user['full_name']));
                exit;

            } else {
                $message = "❌ Invalid email/full name or password.";
            }

        } catch (PDOException $e) {
            $message = "⚠ Database error: " . htmlspecialchars($e->getMessage());
        }

    } else {
        $message = "⚠ Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber - Customer Portal</title>
<style>
body {
  font-family:"Segoe UI", Arial, sans-serif;
  background:linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  min-height:100vh; margin:0;
}
form {
  background:#fff; padding:25px; border-radius:15px; width:380px;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
  opacity:0; transform:translateY(40px);
}
.showForm {
  animation:fadeSlide .6s ease forwards;
}
@keyframes fadeSlide {
  from { opacity:0; transform:translateY(40px); }
  to   { opacity:1; transform:translateY(0); }
}
/* Popup */
.popupOverlay {
  position:fixed; top:0; left:0; width:100%; height:100vh;
  background:rgba(0,0,0,0.45); backdrop-filter:blur(10px);
  display:flex; justify-content:center; align-items:center; z-index:9999;
}
.popupBox {
  background:white; padding:25px 35px; border-radius:12px;
  text-align:center; width:350px; animation:pop .35s ease-out;
}
@keyframes pop { from{transform:scale(0.7); opacity:0;} to{transform:scale(1); opacity:1;} }
</style>
</head>

<body>

<!-- Only show popup if registration success -->
<?php if (isset($_GET['msg']) && $_GET['msg']==="success"): ?>
<div class="popupOverlay" id="popupOverlay">
  <div class="popupBox">
    <h3 style="color:#155724;">Thank you!</h3>
    <p>We received your feedback.</p>
    <button onclick="closePopup()" style="padding:10px 30px; background:#0099cc; color:white; border:none; border-radius:8px; cursor:pointer;">OK</button>
  </div>
</div>
<?php endif; ?>

<div style="text-align:center; margin-bottom: 20px;">
  <img src="../SKYTRUFIBER.png" style="width:140px; border-radius:50%; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
</div>

<form id="supportForm" method="POST">
  <h2 style="text-align:center; color:#004466;">Customer Service Portal</h2>

  <label>Email or Full Name:</label>
  <input type="text" name="full_name" required>

  <label>Password:</label>
  <input type="password" id="password" name="password" required>

  <label>Concern / Inquiry:</label>
  <textarea name="concern" required></textarea>

  <button type="submit">Submit</button>

  <?php if ($message): ?><p style="color:red; text-align:center;"><?= htmlspecialchars($message) ?></p><?php endif; ?>

  <p style="text-align:center;">No account yet? <a href="consent.php">Register here</a></p>
</form>

<script>
// If NOT from registration success → show form immediately
<?php if (!isset($_GET['msg'])): ?>
document.getElementById("supportForm").classList.add("showForm");
<?php endif; ?>

// If popup exists → reveal form after OK
function closePopup() {
  document.getElementById("popupOverlay").style.display = "none";
  document.getElementById("supportForm").classList.add("showForm");
  window.history.replaceState(null, '', window.location.pathname);
}
</script>

</body>
</html>
