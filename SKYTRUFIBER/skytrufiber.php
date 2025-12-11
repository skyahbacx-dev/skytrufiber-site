<?php
session_start();
require_once __DIR__ . '/../db_connect.php';   // correct include path

$message = '';

/* ============================================================
   LOGIN HANDLER
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'], $_POST['password'])) {

    $input    = trim($_POST['full_name']);
    $password = $_POST['password'];
    $concern  = trim($_POST['concern'] ?? '');

    if ($input && $password) {
        try {

            // Fetch user
            $stmt = $conn->prepare("
                SELECT *
                FROM users
                WHERE email = :input OR full_name = :input
                LIMIT 1
            ");
            $stmt->execute([':input' => $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                // Fetch latest ticket
                $ticketStmt = $conn->prepare("
                    SELECT id, status
                    FROM tickets
                    WHERE client_id = :cid
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $ticketStmt->execute([':cid' => $user['id']]);
                $lastTicket = $ticketStmt->fetch(PDO::FETCH_ASSOC);

                // Create new ticket if none or resolved
                if (!$lastTicket || $lastTicket['status'] === 'resolved') {

                    $newTicket = $conn->prepare("
                        INSERT INTO tickets (client_id, status, created_at)
                        VALUES (:cid, 'unresolved', NOW())
                    ");
                    $newTicket->execute([':cid' => $user['id']]);

                    $ticketId = $conn->lastInsertId();

                } else {
                    $ticketId = $lastTicket['id'];
                }

                // Store session
                $_SESSION['client_id'] = $user['id'];
                $_SESSION['ticket_id'] = $ticketId;

                // Insert initial concern
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

                // Redirect to chat UI
                header("Location: /SKYTRUFIBER/chat/chat_support.php?ticket=$ticketId");

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
<title>SkyTruFiber Customer Portal</title>

<style>
/* ------------------------------------------------------------
   GLOBAL LAYOUT
------------------------------------------------------------ */
body { 
    margin:0; 
    font-family:"Segoe UI", Arial, sans-serif; 
    background:linear-gradient(to bottom right, #cceeff, #e6f7ff);
    display:flex; 
    justify-content:center; 
    align-items:center; 
    min-height:100vh; 
}

/* ------------------------------------------------------------
   MAIN FORM CONTAINER
------------------------------------------------------------ */
.container {
    background:rgba(255,255,255,0.55);
    padding:30px;
    border-radius:20px;
    backdrop-filter:blur(12px);
    box-shadow:0 8px 25px rgba(0,0,0,0.15);
    width:380px;
    text-align:center;
    position:relative;
    overflow:hidden;
}

/* RESPONSIVE FIX */
@media (max-width: 600px) {
    body {
        display:block !important;
        padding-top:24px !important;
        min-height:auto !important;
    }
    .container {
        width:92% !important;
        padding:24px !important;
        border-radius:16px;
        margin:0 auto;
    }
    .container img {
        width:120px !important;
        margin-bottom:12px;
    }
}

/* LOGO */
.container img {
    width:150px; 
    border-radius:50%; 
    margin-bottom:15px;
    transition:.3s;
}

/* FORM ANIMATION */
form { 
    transition:opacity .6s ease, transform .6s ease; 
}
.hidden { 
    opacity:0; 
    transform:translateY(-20px); 
    pointer-events:none; 
    position:absolute; 
    top:0; 
    left:0; 
    width:100%; 
}

/* INPUTS */
input, textarea {
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
    box-sizing:border-box;
}
textarea { height:80px; resize:none; }

/* BUTTON */
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
    margin-top:10px;
    transition:.2s;
}
button:hover { background:#008c96; transform:translateY(-2px); }

/* LINKS */
a { 
    display:block; 
    margin-top:10px; 
    color:#0077a3; 
    text-decoration:none; 
    cursor:pointer; 
}
a:hover { text-decoration:underline; }

/* MESSAGE */
.message { 
    font-size:0.9em; 
    margin-bottom:8px; 
}
.message.error { color:red; }
</style>
</head>

<body>
<div class="container">

    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    <h2>Customer Service Portal</h2>

<?php if ($message): ?>
<p class="message error"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- LOGIN FORM -->
<form id="loginForm" method="POST">
    <input type="text" name="full_name" placeholder="Email or Full Name" required>
    <input type="password" name="password" placeholder="Password" required>
    <textarea name="concern" placeholder="Concern / Inquiry"></textarea>
    <button type="submit">Submit</button>
    <a id="forgotLink">Forgot Password?</a>
</form>

<!-- FORGOT PASSWORD FORM -->
<form id="forgotForm" class="hidden">
    <p>Enter your email to receive your account number:</p>
    <input type="email" name="forgot_email" placeholder="Email" required>
    <button type="submit">Send my account number</button>
    <p class="message" id="forgotMessage"></p>
    <a id="backToLogin">Back to Login</a>
</form>

<!-- IMPORTANT: Clean route so index.php encrypts it -->
<p>No account yet? <a href="/fiber/consent">Register here</a></p>

</div>

<script>
const loginForm = document.getElementById('loginForm');
const forgotForm = document.getElementById('forgotForm');
const forgotLink = document.getElementById('forgotLink');
const backToLogin = document.getElementById('backToLogin');

forgotLink.addEventListener('click', e => {
    e.preventDefault();
    loginForm.classList.add('hidden');
    forgotForm.classList.remove('hidden');
});

backToLogin.addEventListener('click', e => {
    e.preventDefault();
    forgotForm.classList.add('hidden');
    loginForm.classList.remove('hidden');
});
</script>

</body>
</html>
