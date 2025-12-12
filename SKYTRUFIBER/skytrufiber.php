<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$message = '';

/* ============================================================
   LOGIN HANDLER
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_request'])) {

    $input    = trim($_POST['full_name']);
    $password = $_POST['password'];
    $concern  = trim($_POST['concern_text'] ?? $_POST['concern_dropdown'] ?? '');

    if ($input && $password) {
        try {
            $stmt = $conn->prepare("
                SELECT * FROM users
                WHERE email = :input OR full_name = :input
                LIMIT 1
            ");
            $stmt->execute([':input' => $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $ticketStmt = $conn->prepare("
                    SELECT id, status FROM tickets
                    WHERE client_id = :cid
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $ticketStmt->execute([':cid' => $user['id']]);
                $lastTicket = $ticketStmt->fetch(PDO::FETCH_ASSOC);

                if (!$lastTicket || $lastTicket['status'] === 'resolved') {
                    $newTicket = $conn->prepare("
                        INSERT INTO tickets (client_id, status, created_at)
                        VALUES (:cid, 'unresolved', NOW())
                    ");
                    $newTicket->execute([':cid' => $user['id']]);
                    $ticketId = $conn->lastInsertId();
                    $_SESSION['show_suggestions'] = true;
                } else {
                    $ticketId = $lastTicket['id'];
                }

                $_SESSION['client_id'] = $user['id'];
                $_SESSION['ticket_id'] = $ticketId;

                if (!empty($concern)) {
                    $insert = $conn->prepare("
                        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, created_at)
                        VALUES (:tid, :cid, 'client', :msg, TRUE, NOW())
                    ");
                    $insert->execute([
                        ':tid' => $ticketId,
                        ':cid' => $user['id'],
                        ':msg' => $concern
                    ]);
                }

                header("Location: /fiber/chat?ticket=$ticketId");
                exit;

            } else {
                $message = "❌ Invalid login credentials.";
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
<title>SkyTruFiber Customer Portal</title>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { 
    margin:0;
    font-family:"Segoe UI", Arial, sans-serif;
    background:linear-gradient(to bottom right, #cceeff, #e6f7ff);
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.container {
    background:white;
    padding:32px;
    border-radius:20px;
    box-shadow:0 5px 18px rgba(0,0,0,0.18);
    width:380px;
    text-align:center;
    position: relative;
    overflow: hidden;
}

/* Fade + Lift Animation */
.fadeLiftOut {
    animation: fadeLiftOut 0.35s forwards ease-in-out;
}
.fadeLiftIn {
    animation: fadeLiftIn 0.35s forwards ease-in-out;
}

@keyframes fadeLiftOut {
    from { opacity:1; transform:translateY(0); }
    to   { opacity:0; transform:translateY(-20px); }
}
@keyframes fadeLiftIn {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

.hidden { display:none; }

.container img {
    width:150px;
    margin-bottom:15px;
}

input, select, textarea {
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
}

textarea {
    height:80px;
    resize:none;
    display:none;
}

button {
    width:100%;
    padding:12px;
    background:#00a6b6;
    color:white;
    border:none;
    border-radius:50px;
    cursor:pointer;
    font-weight:bold;
    font-size:16px;
}

button:hover { background:#008c96; }

.small-links {
    margin-top:12px;
    font-size:14px;
}
.small-links a {
    color:#0077a3;
    cursor:pointer;
}

.message {
    color:red;
    font-size:0.9em;
    margin-bottom:8px;
}

/* Forgot Password Form */
#forgotForm input {
    margin-top: 15px;
}
</style>
</head>

<body>

<div class="container">

    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    <h2>Customer Service Portal</h2>

<?php if ($message): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- LOGIN FORM -->
<form id="loginForm" method="POST">
    <input type="hidden" name="login_request" value="1">

    <input type="text" name="full_name" placeholder="Email or Full Name" required>
    <input type="password" name="password" placeholder="Password" required>

    <select name="concern_dropdown" id="concernSelect">
        <option value="">Select Concern / Inquiry</option>
        <option>Slow Internet</option>
        <option>No Connection</option>
        <option>Router LOS Light On</option>
        <option>Intermittent Internet</option>
        <option>Billing Concern</option>
        <option>Account Verification</option>
        <option value="others">Others…</option>
    </select>

    <textarea id="concernText" name="concern_text" placeholder="Type your concern here..."></textarea>

    <button type="submit">Submit</button>
</form>

<!-- FORGOT PASSWORD FORM (hidden by default) -->
<form id="forgotForm" class="hidden">
    <h3>Recover Account Number</h3>
    <p>Enter your email to retrieve your account number.</p>

    <input type="email" id="forgotEmail" placeholder="Enter your email" required>

    <button type="button" id="sendRecovery">Send Email</button>

    <br>
    <a id="backToLogin" style="cursor:pointer; color:#0077a3;">← Back to Login</a>
</form>

<div class="small-links">
    <a href="/fiber/consent">Register here</a> |
    <a id="forgotLink">Forgot Password?</a>
</div>

</div>

<script>
// Handle "Others" dropdown
const concernSelect = document.getElementById("concernSelect");
const concernText   = document.getElementById("concernText");

concernSelect.addEventListener("change", () => {
    concernText.style.display = (concernSelect.value === "others") ? "block" : "none";
});

// Animation Switching
const loginForm = document.getElementById("loginForm");
const forgotForm = document.getElementById("forgotForm");
const forgotLink = document.getElementById("forgotLink");
const backToLogin = document.getElementById("backToLogin");

// Switch to Forgot Password
forgotLink.onclick = () => {
    loginForm.classList.add("fadeLiftOut");
    setTimeout(() => {
        loginForm.classList.add("hidden");
        forgotForm.classList.remove("hidden");
        forgotForm.classList.add("fadeLiftIn");
    }, 300);
};

// Switch back to Login
backToLogin.onclick = () => {
    forgotForm.classList.add("fadeLiftOut");
    setTimeout(() => {
        forgotForm.classList.add("hidden");
        loginForm.classList.remove("hidden");
        loginForm.classList.add("fadeLiftIn");
    }, 300);
};

// AJAX Forgot Password Sender
document.getElementById("sendRecovery").onclick = () => {
    let email = document.getElementById("forgotEmail").value.trim();

    if (email === "") {
        Swal.fire("Missing Email", "Please enter your email.", "warning");
        return;
    }

    fetch("/fiber/forgot_password.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "email=" + encodeURIComponent(email)
    })
    .then(r => r.json())
    .then(res => {
        Swal.fire({
            title: res.success ? "Email Sent!" : "Error",
            text: res.message,
            icon: res.success ? "success" : "error",
            confirmButtonColor: "#00a6b6"
        });

        if (res.success) {
            setTimeout(() => backToLogin.onclick(), 1500);
        }
    });
};
</script>

</body>
</html>
