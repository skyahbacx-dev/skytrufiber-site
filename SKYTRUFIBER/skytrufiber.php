<?php
session_start();
include '../db_connect.php';

$message = '';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'], $_POST['password'])) {

    $input   = trim($_POST['full_name']);
    $password = $_POST['password'];
    $concern  = trim($_POST['concern'] ?? '');

    if ($input && $password) {
        try {

            // Fetch user by email or fullname
            $stmt = $conn->prepare("
                SELECT *
                FROM users
                WHERE email = :input OR full_name = :input
                ORDER BY id ASC
                LIMIT 1
            ");
            $stmt->execute([':input' => $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                // Retrieve latest ticket
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
                    $ticketWasEmpty = true;

                } else {
                    $ticketId = $lastTicket['id'];
                    $ticketWasEmpty = false;
                }

                // Session variables
                $_SESSION['client_id']   = $user['id'];
                $_SESSION['client_name'] = $user['full_name'];
                $_SESSION['email']       = $user['email'];
                $_SESSION['ticket_id']   = $ticketId;

                // Check if chat already has messages
                $checkMsgs = $conn->prepare("
                    SELECT COUNT(*) FROM chat
                    WHERE ticket_id = :tid
                ");
                $checkMsgs->execute([':tid' => $ticketId]);
                $hasExistingMsgs = ($checkMsgs->fetchColumn() > 0);

                // ---------------------------------------------------
                // CORRECT ORDER FIX:
                // Insert CSR greeting FIRST if this is a new ticket
                // ---------------------------------------------------
                if (!$hasExistingMsgs) {

                    $autoGreet = $conn->prepare("
                        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
                        VALUES (:tid, :cid, 'csr', 'Good day! How may we assist you today?', TRUE, FALSE, NOW())
                    ");
                    $autoGreet->execute([
                        ':tid' => $ticketId,
                        ':cid' => $user['id']
                    ]);

                    // Enable suggestion bubble
                    $_SESSION['show_suggestions'] = true;
                }

                // ---------------------------------------------------
                // INSERT CLIENT'S INITIAL CONCERN (SECOND)
                // ---------------------------------------------------
                if (!empty($concern)) {

                    $insert = $conn->prepare("
                        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
                        VALUES (:tid, :cid, 'client', :msg, TRUE, FALSE, NOW())
                    ");
                    $insert->execute([
                        ':tid' => $ticketId,
                        ':cid' => $user['id'],
                        ':msg' => $concern
                    ]);
                }

                // Redirect to chat UI
                header("Location: chat/chat_support.php?ticket=" . $ticketId);
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
body { font-family:"Segoe UI", Arial, sans-serif; background:linear-gradient(to bottom right, #cceeff, #e6f7ff); display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
.container { background:rgba(255,255,255,0.5); padding:30px; border-radius:20px; backdrop-filter:blur(12px); box-shadow:0 8px 25px rgba(0,0,0,0.15); width:380px; text-align:center; position: relative; overflow: hidden; }
.container img { width:150px; border-radius:50%; margin-bottom:15px; }
form { transition: opacity 0.6s ease, transform 0.6s ease; }
.hidden { opacity:0; transform: translateY(-20px); pointer-events:none; position:absolute; top:0; left:0; width:100%; }
input, textarea { width:100%; padding:10px; margin:8px 0; border-radius:10px; border:1px solid #ccc; box-sizing:border-box; }
textarea { height:80px; resize:none; }
button { width:100%; padding:12px; background:#00a6b6; color:white; border:none; border-radius:50px; cursor:pointer; font-weight:bold; font-size:16px; margin-top:10px; }
button:hover { background:#008c96; transform:translateY(-2px); }
button:active { transform:scale(.97); }
a { display:block; margin-top:10px; color:#0077a3; text-decoration:none; cursor:pointer; }
a:hover { text-decoration:underline; }
.message { font-size:0.9em; margin-bottom:8px; }
.message.success { color:green; }
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
    <p class="message" id="forgotMessage"></p>
    <a id="backToLogin">Back to Login</a>
</form>

<p>No account yet? <a href="consent.php">Register here</a></p>

</div>

<script>
const loginForm = document.getElementById('loginForm');
const forgotForm = document.getElementById('forgotForm');
const forgotLink = document.getElementById('forgotLink');
const backToLogin = document.getElementById('backToLogin');
const forgotMessage = document.getElementById('forgotMessage');

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
