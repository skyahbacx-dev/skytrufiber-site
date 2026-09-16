<?php
/* ============================================================
   SUPER ADMIN — User Management
   Create / edit / deactivate staff logins and reset passwords.
   Super-admin only. Read + write to csr_users.
============================================================ */

ini_set("session.name", "CSRSESSID");
session_start();

if (empty($_SESSION['csr_user'])) {
    header("Location: /csr");
    exit;
}

require __DIR__ . "/../../db_connect.php";
require __DIR__ . "/superadmin_guard.php";

$me = $_SESSION['csr_user'];

function sa_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }

$flash = "";
$flashOk = true;

if (!empty($GLOBALS['CSR_IS_SUPERADMIN']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $u  = trim($_POST['username'] ?? '');
            $fn = trim($_POST['full_name'] ?? '');
            $em = trim($_POST['email'] ?? '');
            $pw = (string)($_POST['password'] ?? '');
            $role = ($_POST['role'] ?? 'csr') === 'superadmin' ? 'superadmin' : 'csr';

            if ($u === '' || $fn === '' || $pw === '') {
                throw new Exception("Username, full name, and password are required.");
            }
            if (strlen($pw) < 8) {
                throw new Exception("Password must be at least 8 characters.");
            }
            $chk = $conn->prepare("SELECT id FROM csr_users WHERE username = :u LIMIT 1");
            $chk->execute([':u' => $u]);
            if ($chk->fetch()) throw new Exception("That username already exists.");

            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO csr_users (username, password, full_name, email, role, status, created_at) VALUES (:u, :p, :fn, :em, :r, 'active', NOW())");
            $ins->execute([':u' => $u, ':p' => $hash, ':fn' => $fn, ':em' => ($em ?: null), ':r' => $role]);
            $flash = "Created user " . $u . ".";

        } elseif ($action === 'update') {
            $u  = trim($_POST['username'] ?? '');
            $fn = trim($_POST['full_name'] ?? '');
            $em = trim($_POST['email'] ?? '');
            $role = ($_POST['role'] ?? 'csr') === 'superadmin' ? 'superadmin' : 'csr';
            $status = (strtolower($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

            if ($u === $me && ($role !== 'superadmin' || $status !== 'active')) {
                throw new Exception("You can't remove your own super admin access or deactivate yourself.");
            }
            $st = $conn->prepare("UPDATE csr_users SET full_name = :fn, email = :em, role = :r, status = :s WHERE username = :u");
            $st->execute([':fn' => $fn, ':em' => ($em ?: null), ':r' => $role, ':s' => $status, ':u' => $u]);
            $flash = "Updated " . $u . ".";

        } elseif ($action === 'reset') {
            $u  = trim($_POST['username'] ?? '');
            $pw = (string)($_POST['password'] ?? '');
            if (strlen($pw) < 8) throw new Exception("New password must be at least 8 characters.");
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $st = $conn->prepare("UPDATE csr_users SET password = :p WHERE username = :u");
            $st->execute([':p' => $hash, ':u' => $u]);
            $flash = "Password reset for " . $u . ".";

        } elseif ($action === 'toggle') {
            $u = trim($_POST['username'] ?? '');
            if ($u === $me) throw new Exception("You can't deactivate your own account.");
            $st = $conn->prepare("UPDATE csr_users SET status = CASE WHEN LOWER(status)='active' THEN 'inactive' ELSE 'active' END WHERE username = :u");
            $st->execute([':u' => $u]);
            $flash = "Changed status for " . $u . ".";
        }
    } catch (Exception $e) {
        $flash = $e->getMessage();
        $flashOk = false;
    }
}

/* Load users */
$users = [];
if (!empty($GLOBALS['CSR_IS_SUPERADMIN'])) {
    try {
        $users = $conn->query("SELECT * FROM csr_users ORDER BY LOWER(role) DESC, username ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $users = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management — Super Admin</title>
<style>
:root{ --green:#007c3c; --green-dark:#015f2e; --line:#e2ece6; --bg:#f4f8f5; --muted:#6b7d73; }
*{box-sizing:border-box;}
body{margin:0;font-family:"Segoe UI",system-ui,Arial,sans-serif;color:#1f2b25;background:var(--bg);}
.top{display:flex;align-items:center;gap:14px;background:var(--green);color:#fff;padding:10px 18px;flex-wrap:wrap;}
.top img{height:34px;width:34px;border-radius:8px;background:#fff;padding:3px;object-fit:contain;}
.top h1{font-size:16px;margin:0;font-weight:700;letter-spacing:.3px;}
.top .nav{margin-left:auto;display:flex;gap:8px;}
.top a{color:#eafff3;font-size:13px;text-decoration:none;padding:7px 12px;border-radius:8px;background:rgba(255,255,255,.14);}
.top a:hover{background:rgba(255,255,255,.26);}
.wrap{max-width:1080px;margin:0 auto;padding:18px;}
.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;margin-bottom:16px;}
.card h3{margin:0 0 12px;color:var(--green-dark);font-size:15px;}
.flash{padding:11px 14px;border-radius:10px;margin-bottom:14px;font-size:14px;}
.flash.ok{background:#e6f6ec;color:#1c6b3a;}
.flash.err{background:#ffe1e1;color:#a12020;}
label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin:8px 0 3px;}
input,select{width:100%;padding:9px 10px;border:1px solid var(--line);border-radius:9px;font-size:13px;background:#fff;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;}
.btn{margin-top:12px;padding:9px 16px;border:none;border-radius:9px;background:var(--green);color:#fff;font-weight:700;font-size:13px;cursor:pointer;}
.btn:hover{background:var(--green-dark);}
.btn.sm{margin-top:0;padding:7px 12px;font-size:12px;}
.btn.ghost{background:#fff;color:var(--green-dark);border:1px solid var(--green);}
table{width:100%;border-collapse:collapse;}
th,td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);font-size:13px;vertical-align:middle;}
th{color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.4px;}
.badge{font-size:11px;padding:2px 8px;border-radius:20px;font-weight:700;}
.b-sa{background:#e6f1fb;color:#0c447c;}
.b-csr{background:#eef2f0;color:#5f6f66;}
.b-active{background:#e6f6ec;color:#1c6b3a;}
.b-inactive{background:#fdf1d8;color:#8a5b06;}
.manage{display:none;background:var(--bg);border-radius:10px;padding:12px;margin-top:8px;}
.manage .row2{display:flex;gap:16px;flex-wrap:wrap;}
.manage form{flex:1;min-width:240px;}
.you{font-size:11px;color:var(--muted);}
.restrict{max-width:520px;margin:80px auto;background:#fff;border:1px solid var(--line);border-radius:16px;padding:34px;text-align:center;}
.restrict h2{color:var(--green-dark);margin:0 0 8px;}
</style>
</head>
<body>

<div class="top">
  <img src="/AHBALOGO.png" alt="AHBA" onerror="this.style.display='none'">
  <h1>USER MANAGEMENT — SUPER ADMIN</h1>
  <div class="nav">
    <a href="/csr/dashboard">← Dashboard</a>
    <a href="/csr/logout">Logout</a>
  </div>
</div>

<?php if (empty($GLOBALS['CSR_IS_SUPERADMIN'])): ?>

  <div class="restrict">
    <h2>Access restricted</h2>
    <p style="color:var(--muted);">This area is for super admins only. You are signed in as
    <strong><?= sa_e($me) ?></strong>.</p>
  </div>

<?php else: ?>

<div class="wrap">

  <?php if ($flash): ?>
    <div class="flash <?= $flashOk ? 'ok' : 'err' ?>"><?= sa_e($flash) ?></div>
  <?php endif; ?>

  <div class="card">
    <h3>Create a new staff login</h3>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="grid">
        <div><label>Username</label><input type="text" name="username" autocomplete="off" required></div>
        <div><label>Full name</label><input type="text" name="full_name" required></div>
        <div><label>Email (optional)</label><input type="email" name="email" autocomplete="off"></div>
        <div><label>Temporary password (8+ chars)</label><input type="password" name="password" autocomplete="new-password" required></div>
        <div><label>Role</label>
          <select name="role">
            <option value="csr">Regular CSR</option>
            <option value="superadmin">Super admin</option>
          </select>
        </div>
      </div>
      <button class="btn" type="submit">Create user</button>
    </form>
  </div>

  <div class="card">
    <h3>Staff accounts (<?= count($users) ?>)</h3>
    <table>
      <thead>
        <tr><th>Username</th><th>Full name</th><th>Email</th><th>Role</th><th>Status</th><th>Last seen</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $usr):
        $uname  = (string)($usr['username'] ?? '');
        $role   = strtolower((string)($usr['role'] ?? 'csr'));
        $isSA   = ($role === 'superadmin' || $role === 'super_admin');
        $status = strtolower((string)($usr['status'] ?? 'active'));
        $isActive = ($status === 'active');
        $seen   = $usr['last_seen'] ?? '';
        $seenTxt = $seen ? date("M j, Y g:i A", strtotime((string)$seen)) : "—";
        $rowId  = "u_" . preg_replace('/[^a-zA-Z0-9]/', '_', $uname);
      ?>
        <tr>
          <td><strong><?= sa_e($uname) ?></strong><?= $uname === $me ? ' <span class="you">(you)</span>' : '' ?></td>
          <td><?= sa_e($usr['full_name'] ?? '') ?></td>
          <td><?= sa_e($usr['email'] ?? '') ?></td>
          <td><span class="badge <?= $isSA ? 'b-sa' : 'b-csr' ?>"><?= $isSA ? 'Super admin' : 'CSR' ?></span></td>
          <td><span class="badge <?= $isActive ? 'b-active' : 'b-inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
          <td style="color:var(--muted);"><?= sa_e($seenTxt) ?></td>
          <td style="text-align:right;">
            <button class="btn sm ghost" type="button" onclick="var m=document.getElementById('<?= $rowId ?>');m.style.display=(m.style.display==='block'?'none':'block');">Manage</button>
          </td>
        </tr>
        <tr>
          <td colspan="7" style="border-bottom:none;padding:0;">
            <div class="manage" id="<?= $rowId ?>">
              <div class="row2">
                <form method="POST">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="username" value="<?= sa_e($uname) ?>">
                  <label>Full name</label><input type="text" name="full_name" value="<?= sa_e($usr['full_name'] ?? '') ?>">
                  <label>Email</label><input type="email" name="email" value="<?= sa_e($usr['email'] ?? '') ?>">
                  <label>Role</label>
                  <select name="role">
                    <option value="csr" <?= !$isSA ? 'selected' : '' ?>>Regular CSR</option>
                    <option value="superadmin" <?= $isSA ? 'selected' : '' ?>>Super admin</option>
                  </select>
                  <label>Status</label>
                  <select name="status">
                    <option value="active" <?= $isActive ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= !$isActive ? 'selected' : '' ?>>Inactive</option>
                  </select>
                  <button class="btn sm" type="submit">Save changes</button>
                </form>
                <form method="POST">
                  <input type="hidden" name="action" value="reset">
                  <input type="hidden" name="username" value="<?= sa_e($uname) ?>">
                  <label>Set a new password (8+ chars)</label>
                  <input type="password" name="password" autocomplete="new-password" placeholder="New password">
                  <button class="btn sm" type="submit">Reset password</button>
                </form>
                <?php if ($uname !== $me): ?>
                <form method="POST" style="min-width:180px;">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="username" value="<?= sa_e($uname) ?>">
                  <label>Account status</label>
                  <button class="btn sm ghost" type="submit"><?= $isActive ? 'Deactivate account' : 'Reactivate account' ?></button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No staff accounts found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php endif; ?>

</body>
</html>
