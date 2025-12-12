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

    // FIXED CONCERN HANDLING
    $concern = "";
    if (!empty($_POST['concern_text'])) {
        $concern = trim($_POST['concern_text']);
    } elseif (!empty($_POST['concern_dropdown']) && $_POST['concern_dropdown'] !== "others") {
        $concern = trim($_POST['concern_dropdown']);
    }

    if ($input && $password) {

        try {
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

                // CHECK LAST TICKET
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
                   NEW TICKET + CSR GREETING INSERT
                ============================================================ */
                if (!$lastTicket || $lastTicket['status'] === 'resolved') {

                    // Create new ticket
                    $newTicket = $conn->prepare("
                        INSERT INTO tickets (client_id, status, created_at)
                        VALUES (:cid, 'unresolved', NOW())
                    ");
                    $newTicket->execute([':cid' => $user['id']]);
                    $ticketId = $conn->lastInsertId();

                    // Insert CSR greeting
                    $greet = $conn->prepare("
                        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, created_at)
                        VALUES (:tid, 0, 'csr', 'Hello! This is SkyTruFiber support. How may I assist you today?', TRUE, NOW())
                    ");
                    $greet->execute([':tid' => $ticketId]);

                    $_SESSION['show_suggestions'] = true;

                } else {
                    $ticketId = $lastTicket['id'];
                }

                // SAVE SESSION
                $_SESSION['client_id'] = $user['id'];
                $_SESSION['ticket_id'] = $ticketId;

                /* CLIENT CONCERN INSERT */
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
    position:relative;
    overflow:hidden;
}

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
    box-sizing:border-box;
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
    text-decoration:none;
}

.small-links a:hover { text-decoration:underline; }

.message { 
    color:red; 
    font-size:0.9em; 
    margin-bottom:8px; 
}

/* --- Smooth Transition Fix --- */
.form-box {
    transition: opacity .3s ease, transform .3s ease;
}

.hidden {
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
    height: 0;
    overflow: hidden;
}

.visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
    height: auto;
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
<form id="loginForm" class="form-box visible" method="POST">

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

<!-- FORGOT PASSWORD FORM -->
<form id="forgotForm" class="form-box hidden" onsubmit="return false;">

    <h2>Forgot Password</h2>
    <p style="font-size:14px;">Enter your email and we will send your account number.</p>

    <input type="email" id="forgotEmail" placeholder="Your Email" required>

    <button id="sendForgotBtn">Send Email</button>

    <div class="small-links" style="margin-top:15px;">
        <a href="#" id="backToLogin">← Back to Login</a>
    </div>
</form>

<div class="small-links">
    <a href="/fiber/consent">Register here</a> |
    <a href="#" id="forgotLink">Forgot Password?</a>
</div>

</div>

<script>
// Concern toggle
document.getElementById("concernSelect").addEventListener("change", function(){
    const txt = document.getElementById("concernText");
    txt.style.display = (this.value === "others") ? "block" : "none";
});

// Show Forgot Password
forgotLink.onclick = e => {
    e.preventDefault();
    loginForm.classList.remove("visible");
    loginForm.classList.add("hidden");

    forgotForm.classList.remove("hidden");
    forgotForm.classList.add("visible");
};

// Back to Login
backToLogin.onclick = e => {
    e.preventDefault();
    forgotForm.classList.remove("visible");
    forgotForm.classList.add("hidden");

    loginForm.classList.remove("hidden");
    loginForm.classList.add("visible");
};

// Send forgot email AJAX
sendForgotBtn.onclick = async () => {
    let email = forgotEmail.value.trim();

    if (!email) {
        Swal.fire("Missing Email", "Please enter your email.", "warning");
        return;
    }

    Swal.fire({
        title: "Sending...",
        text: "Please wait...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    let response = await fetch("/fiber/forgot_password.php", {
        method:"POST",
        headers:{ "Content-Type":"application/x-www-form-urlencoded" },
        body:"email=" + encodeURIComponent(email)
    });

    let data = await response.json();

    if (data.success) {
        Swal.fire("Success!", data.message, "success");
        forgotEmail.value = "";
    } else {
        Swal.fire("Error", data.message, "error");
    }
};
</script>

</body>
</html>
