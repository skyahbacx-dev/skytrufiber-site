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
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root{ --green:#007c3c; --green-dark:#015f2e; }
body{
    margin:0;height:100vh;display:flex;justify-content:center;align-items:center;
    font-family:"Segoe UI",Arial;background:linear-gradient(to right,#dffff0,#f2fff7);
}
.login-box{
    width:350px;padding:28px;background:#fff;border-radius:18px;
    box-shadow:0 10px 28px rgba(0,0,0,.18);text-align:center;
}
h2{margin-bottom:15px;font-weight:800;color:var(--green-dark);}
.field{text-align:left;margin-bottom:14px;}
label{font-size:12px;font-weight:700;color:var(--green-dark);margin-bottom:6px;display:block;}
input[type=text],input[type=password]{
    width:100%;padding:12px;border-radius:12px;border:1px solid #d8e6dd;
}
button{
    width:100%;padding:12px;border:none;border-radius:12px;background:var(--green);
    color:#fff;font-weight:800;font-size:15px;cursor:pointer;
}
button:hover{background:var(--green-dark);}
.error{
    background:#ffdede;color:#b40000;padding:8px;border-radius:8px;margin-top:12px;
}
.footer{margin-top:14px;font-size:12px;}
.footer a{font-weight:700;color:var(--green-dark);text-decoration:none;}
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
