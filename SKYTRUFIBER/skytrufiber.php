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
html, body {
    height: 100%;
    overflow-x: hidden;
}

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
    z-index:5;
}

/* ----- FORM TRANSITION ----- */
.form-box{
    transition:opacity .35s ease, transform .35s ease;
}

.hidden{
    opacity:0;
    transform:translateX(-30px);
    pointer-events:none;
}

.container img{
    width:150px;
    margin-bottom:10px;
}

input, select, textarea{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
    box-sizing:border-box;
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

/* Disable SweetAlert blur */
.swal2-container {
    backdrop-filter:none !important;
}
</style>
</head>

<body>

<div class="container">

    <img src="../SKYTRUFIBER.png">

    <!-- LOGIN FORM -->
    <form id="loginForm" class="form-box" method="POST" action="">
        
        <h2>Customer Service Portal</h2>

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
    <form id="forgotForm" class="form-box hidden" onsubmit="return false;">

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
    loginForm.classList.add("hidden");
    forgotForm.classList.remove("hidden");
};

// Back to login
document.getElementById("backToLogin").onclick = e => {
    e.preventDefault();
    forgotForm.classList.add("hidden");
    loginForm.classList.remove("hidden");
};


// ----- AJAX SEND EMAIL -----
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
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let response = await fetch("/fiber/forgot_password.php", {
        method:"POST",
        headers:{ "Content-Type":"application/x-www-form-urlencoded" },
        body:"email=" + encodeURIComponent(email)
    });

    let data = await response.json();

    if (data.success) {
        Swal.fire({
            icon:"success",
            title:"Email Sent!",
            text:data.message,
            confirmButtonColor:"#00a6b6"
        });
        forgotEmail.value = "";
    } else {
        Swal.fire({
            icon:"error",
            title:"Error",
            text:data.message
        });
    }
};

</script>

</body>
</html>
