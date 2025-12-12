<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$message = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Customer Portal</title>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body{
    margin:0;
    font-family:"Segoe UI", Arial;
    background:linear-gradient(to bottom right, #cceeff, #e6f7ff);
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.container{
    background:white;
    padding:32px;
    border-radius:20px;
    width:380px;
    box-shadow:0 5px 18px rgba(0,0,0,.18);
    text-align:center;
    position:relative;
    overflow:hidden;
    height:520px; /* Fix height so forms don't stretch container */
}

.container img{
    width:150px;
    margin-top:10px;
}

/* ---------- FORM SYSTEM (POSITION ABSOLUTE TO PREVENT STRETCH) ---------- */
.form-box{
    position:absolute;
    top:140px;
    left:0;
    width:100%;
    padding:0 32px;
    transition:opacity .35s ease, transform .35s ease;
}

/* DEFAULT STATE FORMS */
#loginForm{
    opacity:1;
    transform:translateX(0);
}

#forgotForm{
    opacity:0;
    transform:translateX(40px);
    pointer-events:none;
}

/* ACTIVE STATE */
#forgotForm.show{
    opacity:1 !important;
    transform:translateX(0) !important;
    pointer-events:auto;
}

#loginForm.hide{
    opacity:0 !important;
    transform:translateX(-40px) !important;
    pointer-events:none;
}

input, select, textarea{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
}

textarea{
    height:80px;
    resize:none;
    display:none;
}

button{
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
button:hover{ background:#008c96; }

.small-links{
    margin-top:12px;
    font-size:14px;
}
.small-links a{
    color:#0077a3;
    text-decoration:none;
}
.small-links a:hover{ text-decoration:underline; }

.message{
    color:red;
    font-size:0.9em;
    margin-bottom:8px;
}
</style>
</head>

<body>

<div class="container">

    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">

    <!-- LOGIN FORM -->
    <form id="loginForm" class="form-box" method="POST" action="/fiber">
        
        <h2>Customer Service Portal</h2>

        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

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

        <div class="small-links">
            <a href="/fiber/consent">Register here</a> |
            <a href="#" id="forgotLink">Forgot Password?</a>
        </div>
    </form>


    <!-- FORGOT PASSWORD FORM -->
    <form id="forgotForm" class="form-box" onsubmit="return false;">

        <h2>Forgot Password</h2>
        <p style="font-size:14px;">Enter your email and we will send your account number.</p>

        <input type="email" id="forgotEmail" placeholder="Your Email" required>

        <button id="sendForgotBtn">Send Email</button>

        <div class="small-links" style="margin-top:15px;">
            <a href="#" id="backToLogin">← Back to Login</a>
        </div>

    </form>

</div>

<script>
// Show textarea for "Others"
document.getElementById("concernSelect").addEventListener("change", function(){
    const txt = document.getElementById("concernText");
    if(this.value === "others"){
        txt.style.display = "block";
    } else {
        txt.style.display = "none";
        txt.value = "";
    }
});

// Switch to forgot password
document.getElementById("forgotLink").onclick = e => {
    e.preventDefault();
    loginForm.classList.add("hide");
    forgotForm.classList.add("show");
};

// Back to login
document.getElementById("backToLogin").onclick = e => {
    e.preventDefault();
    forgotForm.classList.remove("show");
    loginForm.classList.remove("hide");
};

// SEND EMAIL AJAX
document.getElementById("sendForgotBtn").onclick = async () => {

    let email = document.getElementById("forgotEmail").value.trim();

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
        Swal.fire("Email Sent!", data.message, "success");
        forgotEmail.value = "";
    } else {
        Swal.fire("Error", data.message, "error");
    }
};
</script>

</body>
</html>
