<?php
/* ============================================================
   ONE-TIME SUPER ADMIN SETUP  (delete this file after use)
   ------------------------------------------------------------
   Open:  /CSR/superadmin/setup.php?key=THE_SECRET_BELOW
   Creates your first super admin account. Disables itself once
   a super admin exists. You choose the password — it is never
   stored in this file.
============================================================ */

ini_set("session.name", "CSRSESSID");
session_start();
require __DIR__ . "/../../db_connect.php";

/* Change this before deploying if you like. One-time bootstrap key (NOT a password). */
const SETUP_SECRET = "SA-boot-9Kx7Qm2Rp4Lv8Tn6Ws3Hj";

$key = $_GET['key'] ?? ($_POST['key'] ?? '');
if (!hash_equals(SETUP_SECRET, (string)$key)) {
    http_response_code(403);
    exit("Forbidden.");
}

/* Ensure the role column exists (idempotent) */
try {
    $c = $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='csr_users' AND column_name='role' LIMIT 1");
    if (!$c || !$c->fetch()) {
        $conn->exec("ALTER TABLE csr_users ADD COLUMN role VARCHAR(20) DEFAULT 'csr'");
    }
} catch (Exception $e) {
    // continue; creation will surface any real problem
}

/* Already configured? */
$already = false;
try {
    $q = $conn->query("SELECT 1 FROM csr_users WHERE LOWER(role) = 'superadmin' LIMIT 1");
    $already = ($q && $q->fetch());
} catch (Exception $e) {
    $already = false;
}

$msg = "";
$done = false;

if (!$already && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u   = trim($_POST['username'] ?? '');
    $fn  = trim($_POST['full_name'] ?? '');
    $em  = trim($_POST['email'] ?? '');
    $pw  = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['confirm'] ?? '');

    if ($u === '' || $fn === '' || $pw === '') {
        $msg = "Please fill in username, full name, and password.";
    } elseif ($pw !== $pw2) {
        $msg = "The two passwords do not match.";
    } elseif (strlen($pw) < 8) {
        $msg = "Please use a password of at least 8 characters.";
    } else {
        try {
            $chk = $conn->prepare("SELECT id FROM csr_users WHERE username = :u LIMIT 1");
            $chk->execute([':u' => $u]);
            $hash = password_hash($pw, PASSWORD_DEFAULT);

            if ($chk->fetch()) {
                $st = $conn->prepare("UPDATE csr_users SET password = :p, full_name = :fn, email = :em, role = 'superadmin', status = 'active' WHERE username = :u");
                $st->execute([':p' => $hash, ':fn' => $fn, ':em' => ($em ?: null), ':u' => $u]);
            } else {
                $st = $conn->prepare("INSERT INTO csr_users (username, password, full_name, email, role, status, created_at) VALUES (:u, :p, :fn, :em, 'superadmin', 'active', NOW())");
                $st->execute([':u' => $u, ':p' => $hash, ':fn' => $fn, ':em' => ($em ?: null)]);
            }
            $done = true;
        } catch (Exception $e) {
            $msg = "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Setup</title>
<style>
:root{ --green:#007c3c; --green-dark:#015f2e; }
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
  font-family:"Segoe UI",system-ui,Arial,sans-serif;background:linear-gradient(to right,#e8f7ee,#f2fff7);}
.box{width:420px;max-width:92%;background:#fff;border-radius:16px;padding:30px;
  box-shadow:0 10px 28px rgba(0,0,0,.14);}
h2{color:var(--green-dark);text-align:center;margin:0 0 6px;}
p.sub{color:#6b7d73;font-size:13px;text-align:center;margin:0 0 18px;}
label{display:block;font-size:13px;font-weight:600;color:var(--green-dark);margin:12px 0 4px;}
input{width:100%;box-sizing:border-box;padding:11px;border:1px solid #d8e6dd;border-radius:10px;font-size:14px;}
button{width:100%;margin-top:18px;padding:12px;border:none;border-radius:10px;background:var(--green);
  color:#fff;font-weight:700;font-size:15px;cursor:pointer;}
button:hover{background:var(--green-dark);}
.msg{margin-top:14px;padding:10px;border-radius:8px;font-size:13px;background:#ffe1e1;color:#a12020;text-align:center;}
.ok{background:#e6f6ec;color:#1c6b3a;}
.note{margin-top:16px;font-size:12px;color:#8a5b06;background:#fdf1d8;border-radius:8px;padding:10px;}
</style>
</head>
<body>
<div class="box">
  <h2>Super Admin Setup</h2>
  <p class="sub">SkyTruFiber / AHBA — one-time account creation</p>

<?php if ($already && !$done): ?>
  <div class="msg ok">A super admin already exists. This setup is now disabled.</div>
  <div class="note">For security, please delete the file <code>CSR/superadmin/setup.php</code> from your repository.</div>

<?php elseif ($done): ?>
  <div class="msg ok">Super admin created. You can now log in at <b>/csr</b> with the username and password you just chose.</div>
  <div class="note">Important: delete the file <code>CSR/superadmin/setup.php</code> now so it can never be used again.</div>

<?php else: ?>
  <form method="POST">
    <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
    <label>Username</label>
    <input type="text" name="username" autocomplete="off" required>
    <label>Full name</label>
    <input type="text" name="full_name" required>
    <label>Email (optional)</label>
    <input type="email" name="email" autocomplete="off">
    <label>Password (at least 8 characters)</label>
    <input type="password" name="password" autocomplete="new-password" required>
    <label>Confirm password</label>
    <input type="password" name="confirm" autocomplete="new-password" required>
    <button type="submit">Create super admin</button>
  </form>
  <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
  <div class="note">You choose this password. It is encrypted and never stored in the code.</div>
<?php endif; ?>

</div>
</body>
</html>
