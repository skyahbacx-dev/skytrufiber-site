<?php
session_start();
include '../db_connect.php';

// If already logged in
if (isset($_SESSION['csr_user']) && $_SESSION['csr_user'] !== '') {
    header("Location: csr_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === "" || $password === "") {
        $error = "Please fill in both fields.";
    } else {
        $stmt = $conn->prepare("SELECT username, password, fullname, status FROM csr_users WHERE username = :u LIMIT 1");
        $stmt->execute([":u" => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && strtolower($row['status']) === "active") {
            if (password_verify($password, $row['password']) ||
                (strlen($row['password']) === 32 && md5($password) === strtolower($row['password']))) {

                $_SESSION['csr_user'] = $row['username'];
                $_SESSION['csr_fullname'] = $row['fullname'];

                $update = $conn->prepare("UPDATE csr_users SET last_seen = NOW() WHERE username = :u");
                $update->execute([":u" => $row['username']]);

                header("Location: csr_dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Account not found or inactive.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root{
    --green:#007c3c;
    --green-dark:#015f2e;
}
body{
    margin:0;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:"Segoe UI", Arial, sans-serif;
    background:linear-gradient(to right,#dffff0,#f2fff7);
    position:relative;
}
body::before{
    content:"";
    position:absolute;
    inset:0;
    background:url('../AHBALOGO.png') no-repeat center center;
    background-size:600px auto;
    opacity:.08;
    z-index:0;
}
.login-box{
    position:relative;
    z-index:2;
    width:350px;
    padding:28px;
    background:#fff;
    border-radius:18px;
    box-shadow:0 10px 28px rgba(0,0,0,.18);
    text-align:center;
}
h2{
    margin-bottom:15px;
    font-weight:800;
    color:var(--green-dark);
}
.field{
    text-align:left;
    margin-bottom:14px;
}
label{
    display:block;
    font-size:12px;
    font-weight:700;
    color:var(--green-dark);
    margin-bottom:6px;
}
input[type=text],input[type=password]{
    width:100%;
    padding:12px 12px;
    border-radius:12px;
    border:1px solid #d8e6dd;
    font-size:14px;
}
button{
    margin-top:6px;
    width:100%;
    padding:12px 12px;
    border:none;
    border-radius:12px;
    background:var(--green);
    color:#fff;
    font-weight:800;
    font-size:15px;
    cursor:pointer;
}
button:hover{background:var(--green-dark);}
.error{
    margin-top:12px;
    background:#ffdede;
    color:#b40000;
    padding:8px;
    border-radius:8px;
    font-size:13px;
    font-weight:600;
    border:1px solid #ffbfbf;
}
.footer{
    margin-top:14px;
    font-size:12px;
}
.footer a{
    color:var(--green-dark);
    font-weight:700;
    text-decoration:none;
}
.toggle{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    margin-bottom:10px;
}
</style>
</head>
<body>

<div class="login-box">
    <h2>CSR LOGIN</h2>

    <form method="POST">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="field">
            <label>Password</label>
            <input type="password" name="password" id="pwd" required>
        </div>

        <label class="toggle">
            <input type="checkbox" onclick="togglePass()"> Show password
        </label>

        <button type="submit">Login</button>

        <?php if($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
    </form>

    <div class="footer">
        Back to <a href="../dashboard.php">Home</a>
    </div>
</div>

<script>
function togglePass(){
    let p = document.getElementById("pwd");
    p.type = (p.type === "password") ? "text" : "password";
}
</script>

</body>
</html>
