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

                // SET SESSION
                $_SESSION['user'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];

                // INSERT first concern into chat DB
                if (!empty($concern)) {
                    $insert = $conn->prepare("
                        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
                        VALUES (:cid, 'client', :msg, false, false, NOW())
                    ");
                    $insert->execute([
                        ':cid' => $user['id'],
                        ':msg' => $concern
                    ]);
                }

                // REDIRECT TO CHAT PAGE
               header("Location: SKYTRUFIBER/chat/chat_support.php?username=" . urlencode($user['full_name']));
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

/* Slide-in logo animation */
@keyframes slideLogo {
  from { opacity:0; transform:translateY(-60px); }
  to { opacity:1; transform:translateY(0); }
}

.logo {
  animation: slideLogo .9s ease-out forwards;
}

/* Glass morph form */
form {
  background:rgba(255,255,255,0.45);
  padding:25px;
  border-radius:20px;
  width:380px;
  backdrop-filter:blur(12px);
  box-shadow:0 8px 25px rgba(0,0,0,0.15);
  opacity:0;
  transform:translateY(40px);
}

.showForm {
  animation:fadeSlide .6s ease forwards;
}

@keyframes fadeSlide {
  from { opacity:0; transform:translateY(40px); }
  to   { opacity:1; transform:translateY(0); }
}

input, textarea {
  width:100%; padding:10px; margin-top:5px;
  border-radius:10px; border:1px solid #ccc; box-sizing:border-box;
}

textarea { height:80px; resize:none; }

button {
  width:100%; padding:12px;
  background:#00a6b6; color:white; border:none;
  border-radius:50px; cursor:pointer; font-weight:bold; font-size:16px;
  margin-top:15px; position:relative; overflow:hidden;
  transition:background 0.3s, transform .2s;
}

button:hover {
  background:#008c96;
  transform:translateY(-2px);
  box-shadow:0 6px 12px rgba(0,140,150,.4);
}

button:active { transform:scale(.97); }

/* Ripple effect */
button .ripple {
  position:absolute; background:rgba(255,255,255,0.6);
  border-radius:50%; transform:scale(0);
  animation:rippleEffect .6s linear;
}

@keyframes rippleEffect { to { transform:scale(4); opacity:0; } }

label { display:block; margin-top:10px; color:#004466; font-weight:600; }

/* Popup */
.popupOverlay {
  position:fixed; top:0; left:0; width:100%; height:100vh;
  background:rgba(0,0,0,0.45); backdrop-filter:blur(10px);
  display:flex; justify-content:center; align-items:center; z-index:9999;
}

.popupBox {
  background:white; padding:25px 35px; border-radius:12px;
  width:350px; text-align:center;
  animation:pop .35s ease-out;
}

@keyframes pop {
  from { transform:scale(0.7); opacity:0; }
  to   { transform:scale(1); opacity:1; }
}
</style>
</head>

<body>

<?php if (isset($_GET['msg']) && $_GET['msg']=="success"): ?>
<div class="popupOverlay" id="popupOverlay">
  <div class="popupBox">
    <h3 style="color:#155724;">Thank you!</h3>
    <p>We received your feedback.</p>
    <button onclick="closePopup()">OK</button>
  </div>
</div>
<?php endif; ?>

<img src="../SKYTRUFIBER.png" class="logo" style="width:150px; border-radius:50%; margin-bottom:15px; box-shadow:0 2px 6px rgba(0,0,0,0.2);">

<form id="supportForm" method="POST">
  <h2 style="text-align:center; color:#004466;">Customer Service Portal</h2>

  <label>Email or Full Name:</label>
  <input type="text" name="full_name" required>

  <label>Password:</label>
  <div style="position:relative;">
    <input type="password" id="password" name="password" required style="padding-right:40px;">
    <span id="toggleEye" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:18px; color:#007a99;">👁️</span>
  </div>

  <div style="display:flex; align-items:center; margin-top:5px; font-size:13px; color:#004466;">
    <input type="checkbox" id="showPassword" style="width:16px; height:16px; margin-right:6px;">
    <label for="showPassword" style="margin-top:0;">Show Password</label>
  </div>

  <label>Concern / Inquiry:</label>
  <textarea name="concern" required></textarea>

  <button type="submit">Submit</button>

  <?php if ($message): ?>
    <p style="color:red; text-align:center;"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p style="text-align:center; margin-top:10px;">No account yet? <a href="consent.php">Register here</a></p>
</form>

<script>
// form visibility control
<?php if (!isset($_GET['msg'])): ?>
document.getElementById("supportForm").classList.add("showForm");
<?php endif; ?>

function closePopup() {
  document.getElementById("popupOverlay").style.display="none";
  document.getElementById("supportForm").classList.add("showForm");
  window.history.replaceState(null, '', window.location.pathname);
}

// eye icon toggle
document.getElementById("toggleEye").addEventListener("click", function () {
  const pw = document.getElementById("password");
  if (pw.type === "password") { pw.type="text"; this.textContent="🙈"; }
  else { pw.type="password"; this.textContent="👁️"; }
});

// checkbox show/hide
document.getElementById("showPassword").addEventListener("change", function () {
  document.getElementById("password").type = this.checked ? "text" : "password";
});

// ripple button
document.querySelector("button[type='submit']").addEventListener("click", function (e) {
  const circle = document.createElement("span");
  const diameter = Math.max(this.clientWidth, this.clientHeight);
  circle.style.width = circle.style.height = `${diameter}px`;
  circle.style.left = `${e.clientX - (this.offsetLeft + diameter/2)}px`;
  circle.style.top = `${e.clientY - (this.offsetTop + diameter/2)}px`;
  circle.classList.add("ripple");
  this.appendChild(circle);
});
</script>

</body>
</html>
