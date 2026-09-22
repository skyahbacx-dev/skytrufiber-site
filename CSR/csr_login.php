<?php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../home.php';   // ← IMPORTANT: gives encrypt_route()

/* ============================================================
   AUTO REDIRECT IF LOGGED IN
============================================================ */
if (!empty($_SESSION["csr_user"])) {
    $token = encrypt_route("csr_dashboard");
    header("Location: /home.php?v=$token");
    exit;
}

$error = "";

/* ============================================================
   LOGIN HANDLER
============================================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter username & password.";

    } else {

        $stmt = $conn->prepare("
            SELECT username, full_name, password, status 
            FROM csr_users
            WHERE username = :u
            LIMIT 1
        ");
        $stmt->execute([":u" => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && strtolower($row["status"]) === "active") {

            if (password_verify($password, $row["password"])) {

                session_regenerate_id(true);

                $_SESSION["csr_user"]     = $row["username"];
                $_SESSION["csr_fullname"] = $row["full_name"];

                // Update last seen
                $conn->prepare("
                    UPDATE csr_users SET last_seen = NOW() WHERE username = :u
                ")->execute([":u" => $row["username"]]);

                // Redirect to encrypted dashboard
                $token = encrypt_route("csr_dashboard");
                header("Location: /home.php?v=$token");
                exit;

            } else {
                $error = "Invalid password.";
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
<link rel="stylesheet" href="/assets/css/skytru.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root{ --green:#1a4fc4; --green-dark:#123a91; }
body{
    margin:0;height:100vh;display:flex;justify-content:center;align-items:center;
    font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Arial;
    background:linear-gradient(135deg,#e8eefc,#f4f6fa);
}
.login-box{
    width:360px;padding:32px;background:#fff;border-radius:18px;
    box-shadow:0 10px 28px rgba(20,24,40,.12);text-align:center;
}
.login-box img{height:44px;margin-bottom:14px;}
h2{margin:0 0 20px;font-weight:800;color:var(--green-dark);font-size:18px;letter-spacing:.02em;}
.field{text-align:left;margin-bottom:14px;}
label{font-size:12px;font-weight:700;color:#45495a;margin-bottom:6px;display:block;}
input[type=text],input[type=password]{
    width:100%;padding:12px;border-radius:10px;border:1px solid #d7dbe4;font-size:14px;font-family:inherit;
    box-sizing:border-box;
}
input[type=text]:focus,input[type=password]:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px #e8eefc;}
button{
    width:100%;padding:12px;border:none;border-radius:10px;background:var(--green);
    color:#fff;font-weight:700;font-size:14px;cursor:pointer;margin-top:6px;
}
button:hover{background:var(--green-dark);}
.error{
    background:#fbe7e5;color:#c0342b;padding:9px;border-radius:8px;margin-top:12px;font-size:13px;
}
.footer{margin-top:16px;font-size:12px;color:#737a8c;}
.footer a{font-weight:700;color:var(--green-dark);text-decoration:none;}
</style>
</head>

<body>

<div class="login-box">
    <img src="/AHBALOGO.png" alt="" onerror="this.style.display='none'">
    <h2>CSR Sky — Agent Login</h2>

    <form method="POST">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="field">
            <label>Password</label>
            <input type="password" name="password" id="pwd" required>
        </div>

        <label style="font-size:12px;">
            <input type="checkbox" onclick="togglePass()"> Show password
        </label>

        <button type="submit">Login</button>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </form>

    <div class="footer">
        Back to <a href="/csr">CSR Portal</a>
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