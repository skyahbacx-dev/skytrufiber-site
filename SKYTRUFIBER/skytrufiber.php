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

    /* Proper concern handling */
    $concern = "";
    if (!empty($_POST['concern_text'])) {
        $concern = trim($_POST['concern_text']);
    } elseif (!empty($_POST['concern_dropdown']) && $_POST['concern_dropdown'] !== "others") {
        $concern = trim($_POST['concern_dropdown']);
    }

    if ($input && $password) {

        try {
            /* Fetch user */
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

                /* Fetch last ticket */
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
                   CREATE NEW TICKET + INSERT CSR GREETING
                ============================================================ */
                if (!$lastTicket || $lastTicket['status'] === 'resolved') {

                    $newTicket = $conn->prepare("
                        INSERT INTO tickets (client_id, status, created_at)
                        VALUES (:cid, 'pending', NOW())
                    ");
                    $newTicket->execute([':cid' => $user['id']]);
                    $ticketId = $conn->lastInsertId();

                    /* Personalized CSR Greeting (FULL NAME) */
                 $csrGreeting = "Hello {$user['full_name']}! This is SkyTruFiber Support. How may I assist you today?";

                  $greet = $conn->prepare("
                   INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
                   VALUES (:tid, NULL, 'csr', :msg, TRUE, TRUE, NOW())
                  ");

                  $greet->execute([
                   ':tid' => $ticketId,
                   ':msg' => $csrGreeting
                  ]);


                    $_SESSION['show_suggestions'] = true;

                } else {
                    $ticketId = $lastTicket['id'];
                }

                /* Save session */
                $_SESSION['client_id'] = $user['id'];
                $_SESSION['ticket_id'] = $ticketId;

                /* Insert Client Inquiry */
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
  --green-main:#0f6b4d;
  --green-dark:#0a2e22;
  --gold:#d4a34a;
  --gold-dark:#b8862f;
}

*{ box-sizing:border-box; }

body{
    margin:0;
    font-family:"Plus Jakarta Sans","Segoe UI",Arial,sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#f2f6f4;
    padding:24px;
}

.portal-wrap{
    display:flex;
    width:100%;
    max-width:900px;
    min-height:560px;
    background:#fff;
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 30px 70px rgba(10,46,34,.18);
}

/* ---------- LEFT: brand panel ---------- */
.portal-brand{
    flex:1;
    position:relative;
    overflow:hidden;
    background:radial-gradient(ellipse 120% 100% at 30% 0%, #123d2c 0%, var(--green-dark) 55%, #061a13 100%);
    color:#fff;
    padding:44px 38px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
}

.portal-brand::before{
    content:"";
    position:absolute; top:-15%; right:-20%;
    width:320px; height:320px;
    background:radial-gradient(circle, rgba(212,163,74,.35), transparent 70%);
    filter:blur(10px);
    pointer-events:none;
}
.portal-brand::after{
    content:"";
    position:absolute; bottom:-20%; left:-15%;
    width:280px; height:280px;
    background:radial-gradient(circle, rgba(15,107,77,.55), transparent 70%);
    filter:blur(6px);
    pointer-events:none;
}

.portal-brand img{ width:120px; z-index:1; }

.portal-brand h1{
    font-size:24px;
    font-weight:800;
    line-height:1.3;
    margin:26px 0 12px;
    z-index:1;
}

.portal-brand p{
    font-size:14px;
    color:rgba(255,255,255,.78);
    z-index:1;
    max-width:280px;
}

.portal-perks{ list-style:none; margin-top:28px; z-index:1; }
.portal-perks li{
    display:flex; align-items:center; gap:10px;
    font-size:13px; color:rgba(255,255,255,.85);
    padding:8px 0;
}
.portal-perks li::before{
    content:"✓";
    display:flex; align-items:center; justify-content:center;
    width:20px; height:20px; border-radius:50%;
    background:rgba(212,163,74,.25); color:var(--gold);
    font-size:11px; font-weight:800; flex-shrink:0;
}

.portal-back{
    z-index:1;
    font-size:12px;
    color:rgba(255,255,255,.6);
    text-decoration:none;
}
.portal-back:hover{ color:var(--gold); }

/* ---------- RIGHT: form panel ---------- */
.portal-form-panel{
    flex:1.1;
    padding:44px 40px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.portal-form-panel h2{
    font-size:22px;
    font-weight:800;
    color:var(--green-dark);
    margin-bottom:4px;
}
.portal-form-panel > p.subtitle{
    font-size:13px;
    color:#6b7280;
    margin-bottom:20px;
}

input, select, textarea {
    width:100%;
    padding:13px 14px;
    margin:8px 0;
    border-radius:12px;
    border:1.5px solid #e2e8e5;
    font-size:14px;
    font-family:inherit;
    transition:border-color .2s ease, box-shadow .2s ease;
}
input:focus, select:focus, textarea:focus{
    outline:none;
    border-color:var(--green-main);
    box-shadow:0 0 0 3px rgba(15,107,77,.12);
}

textarea{
    height:80px;
    resize:none;
    display:none;
}

button{
    width:100%;
    padding:13px;
    background:var(--gold);
    color:var(--green-dark);
    border:none;
    border-radius:999px;
    cursor:pointer;
    font-size:15px;
    font-weight:700;
    margin-top:10px;
    transition:background .2s ease, transform .2s ease, box-shadow .2s ease;
    box-shadow:0 8px 18px rgba(212,163,74,.3);
}
button:hover{ background:var(--gold-dark); transform:translateY(-2px); }

.small-links{
    margin-top:14px;
    font-size:13px;
    color:#6b7280;
    text-align:center;
}

.small-links a{
    color:var(--green-main);
    font-weight:600;
    text-decoration:none;
}

.small-links a:hover{ color:var(--gold-dark); text-decoration:underline; }

.message{
    color:#c0342b;
    background:#fbe7e5;
    padding:10px 14px;
    border-radius:10px;
    font-size:13.5px;
    margin-bottom:10px;
}

/* Animation */
.form-box {
    transition: opacity .3s ease, transform .3s ease, height .3s ease;
}

.hidden {
    opacity: 0;
    transform: translateY(20px);
    height: 0;
    overflow: hidden;
    pointer-events: none;
}

.visible {
    opacity: 1;
    transform: translateY(0);
    height: auto;
    pointer-events: auto;
}

@media (max-width:760px){
    .portal-wrap{ flex-direction:column; max-width:420px; min-height:0; }
    .portal-brand{ padding:32px 28px; }
    .portal-perks{ display:none; }
}
</style>
</head>

<body>

<div class="portal-wrap">

    <div class="portal-brand">
        <div>
            <img src="/SKYTRUFIBER.png" alt="SkyTruFiber">
            <h1>Fast, reliable fiber internet for your home.</h1>
            <p>Manage your account and get support from our team, anytime.</p>
            <ul class="portal-perks">
                <li>Track your concern in real time</li>
                <li>Chat directly with a support agent</li>
                <li>Backed by AHBA Development</li>
            </ul>
        </div>
        <a href="/" class="portal-back">← Back to AHBA Development</a>
    </div>

    <div class="portal-form-panel">

        <h2>Customer Service Portal</h2>
        <p class="subtitle">Log in to send a concern or check your account.</p>

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

    <input type="email" id="forgotEmail" placeholder="Your Email">

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
</div>

<script>
// Concern toggle
document.getElementById("concernSelect").addEventListener("change", function(){
    concernText.style.display = (this.value === "others") ? "block" : "none";
});

// Show forgot form
forgotLink.onclick = e => {
    e.preventDefault();
    loginForm.classList.replace("visible","hidden");
    forgotForm.classList.replace("hidden","visible");
};

// Back to login
backToLogin.onclick = e => {
    e.preventDefault();
    forgotForm.classList.replace("visible","hidden");
    loginForm.classList.replace("hidden","visible");
};

// AJAX: Send email
sendForgotBtn.onclick = async () => {

    let email = forgotEmail.value.trim();
    if (!email) {
        Swal.fire("Missing Email","Please enter your email.","warning");
        return;
    }

    Swal.fire({
        title:"Sending...",
        text:"Please wait...",
        allowOutsideClick:false,
        didOpen:()=>Swal.showLoading()
    });

    let response = await fetch("/fiber/forgot_password.php", {
        method:"POST",
        headers:{ "Content-Type":"application/x-www-form-urlencoded" },
        body:"email=" + encodeURIComponent(email)
    });

    let data = await response.json();

    if (data.success){
        Swal.fire("Success!", data.message, "success");
        forgotEmail.value = "";
    } else {
        Swal.fire("Error", data.message, "error");
    }
};
</script>

</body>
</html>