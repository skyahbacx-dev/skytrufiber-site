<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

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
            // Fetch matching user
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

                /* ============================================================
                   FETCH LAST TICKET
                ============================================================ */
                $ticketStmt = $conn->prepare("
                    SELECT id, status
                    FROM tickets
                    WHERE client_id = :cid
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $ticketStmt->execute([':cid' => $user['id']]);
                $lastTicket = $ticketStmt->fetch(PDO::FETCH_ASSOC);

                /* ============================================================
                   CREATE NEW TICKET IF NONE OR RESOLVED
                ============================================================ */
                if (!$lastTicket || $lastTicket['status'] === 'resolved') {

                    $newTicket = $conn->prepare("
                        INSERT INTO tickets (client_id, status, created_at)
                        VALUES (:cid, 'unresolved', NOW())
                    ");
                    $newTicket->execute([':cid' => $user['id']]);

                    $ticketId = $conn->lastInsertId();

                    // Enable auto-suggestions (first greeting package)
                    $_SESSION['show_suggestions'] = true;

                } else {
                    $ticketId = $lastTicket['id'];
                }

                /* ============================================================
                   SAVE SESSION
                ============================================================ */
                $_SESSION['client_id'] = $user['id'];
                $_SESSION['ticket_id'] = $ticketId;

                /* ============================================================
                   INSERT FIRST CLIENT MESSAGE
                ============================================================ */
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

                /* ============================================================
                   REDIRECT TO CHAT UI
                ============================================================ */
               header("Location: /fiber/chat?ticket=$ticketId");
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
    background:rgba(255,255,255,0.55);
    padding:30px;
    border-radius:20px;
    backdrop-filter:blur(12px);
    box-shadow:0 8px 25px rgba(0,0,0,0.15);
    width:380px;
    text-align:center;
}

@media (max-width: 600px) {
    body { display:block !important; padding-top:24px !important; }
    .container { width:92% !important; padding:24px !important; border-radius:16px; }
    .container img { width:120px !important; }
}

.container img {
    width:150px; 
    border-radius:50%; 
    margin-bottom:15px;
}

input, textarea {
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
}
textarea { height:80px; resize:none; }

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

a { display:block; margin-top:10px; color:#0077a3; text-decoration:none; }
a:hover { text-decoration:underline; }

.message { color:red; font-size:0.9em; margin-bottom:8px; }

.hidden { display:none; }
</style>
</head>

<body>

<div class="container">

    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    <h2>Customer Service Portal</h2>

<?php if ($message): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form id="loginForm" method="POST">
    <input type="text" name="full_name" placeholder="Email or Full Name" required>
    <input type="password" name="password" placeholder="Password" required>
    <textarea name="concern" placeholder="Concern / Inquiry"></textarea>
    <button type="submit">Submit</button>
    <a id="forgotLink">Forgot Password?</a>
</form>

<form id="forgotForm" class="hidden">
    <p>Enter your email to receive your account number:</p>
    <input type="email" name="forgot_email" placeholder="Email" required>
    <button type="submit">Send my account number</button>
    <p id="forgotMessage"></p>
    <a id="backToLogin">Back to Login</a>
</form>

<!-- Clean route — index.php encrypts -->
<p>No account yet? <a href="/fiber/consent">Register here</a></p>

</div>

<script>
const loginForm = document.getElementById('loginForm');
const forgotForm = document.getElementById('forgotForm');

document.getElementById('forgotLink').onclick = e => {
    e.preventDefault();
    loginForm.classList.add('hidden');
    forgotForm.classList.remove('hidden');
};

document.getElementById('backToLogin').onclick = e => {
    e.preventDefault();
    forgotForm.classList.add('hidden');
    loginForm.classList.remove('hidden');
};
</script>

</body>
</html>
