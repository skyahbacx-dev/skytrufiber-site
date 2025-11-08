<?php
// CSR/csr_login.php
session_start();
include '../db_connect.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['csr_user']) && $_SESSION['csr_user'] !== '') {
    header("Location: csr_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $pw_input = $_POST['password'] ?? '';

    if ($u === '' || $pw_input === '') {
        $error = "Please enter both username and password.";
    } else {
        try {
            // Fetch user row
            $stmt = $conn->prepare("SELECT username, password, status FROM csr_users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $u]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if (strtolower($row['status']) !== 'active') {
                    $error = "Account is not active.";
                } else {
                    $db_hash = $row['password'];
                    $ok = false;
                    $is_md5 = false;

                    // Try modern password_verify (bcrypt/argon2)
                    if (password_verify($pw_input, $db_hash)) {
                        $ok = true;
                    }
                    // Fallback: legacy MD5 check
                    elseif (strlen($db_hash) === 32 && ctype_xdigit($db_hash) && md5($pw_input) === strtolower($db_hash)) {
                        $ok = true;
                        $is_md5 = true;
                    }

                    if ($ok) {
                        // If MD5 password, rehash to bcrypt and update the DB
                        if ($is_md5) {
                            $new_hash = password_hash($pw_input, PASSWORD_DEFAULT);
                            $update_pw = $conn->prepare("UPDATE csr_users SET password = :new_hash WHERE username = :username");
                            $update_pw->execute([':new_hash' => $new_hash, ':username' => $u]);
                        }

                        // Start secure session
                        session_regenerate_id(true);
                        $_SESSION['csr_user'] = $row['username'];

                        // Optionally track last login only
                        $update = $conn->prepare("UPDATE csr_users SET last_seen = NOW() WHERE username = :username");
                        $update->execute([':username' => $row['username']]);

                        header("Location: csr_dashboard.php");
                        exit;
                    } else {
                        $error = "Invalid username or password.";
                    }
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
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
  :root { --green: #00a800; --green-dark: #009000; }
  html, body { height: 100%; margin: 0; padding: 0; }
  body {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(to right, #c8f8c8, #e7ffe7);
    font-family: "Segoe UI", Arial, sans-serif;
    position: relative;
  }
  body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: url('../AHBALOGO.png') no-repeat center center;
    background-size: 640px auto;
    opacity: 0.08;
    z-index: 0;
    pointer-events: none;
  }
  .box {
    position: relative;
    z-index: 1;
    background: #fff;
    padding: 30px 28px;
    border-radius: 14px;
    box-shadow: 0 10px 24px rgba(0,0,0,.12);
    width: 340px;
    text-align: center;
  }
  .box h2 { color: #066d06; margin-bottom: 16px; font-weight: 800; }
  .field { text-align: left; margin-bottom: 12px; }
  .field label {
    display: block; font-size: 12px; color: #055b05; margin-bottom: 6px; font-weight: 700;
  }
  input[type="text"], input[type="password"] {
    width: 100%; padding: 11px 5px; border-radius: 10px; border: 1px solid #cfd8cf; font-size: 14px;
  }
  input[type="text"]:focus, input[type="password"]:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(0,168,0,.12);
  }
  .toggle { display: flex; align-items: center; gap: 8px; margin: 4px 0 10px; font-size: 12px; color: #333; }
  button[type="submit"] {
    width: 100%; padding: 11px 12px; border-radius: 10px; border: none;
    font-size: 15px; font-weight: 800; letter-spacing: .2px;
    background: var(--green); color: #fff; cursor: pointer;
  }
  button[type="submit"]:hover { background: var(--green-dark); }
  .error {
    color: #c62828; background: #ffebee; border: 1px solid #ffcdd2;
    padding: 8px 10px; border-radius: 8px; font-size: 13px; margin-top: 12px;
  }
  .foot { margin-top: 12px; font-size: 12px; color: #4a4a4a; }
  .foot a { color: #066d06; text-decoration: none; font-weight: 700; }
</style>
</head>
<body>
  <div class="box">
    <h2>CSR Login</h2>
    <form method="POST" autocomplete="off" novalidate>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" type="text" name="username" placeholder="Enter username" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" placeholder="Enter password" required>
      </div>
      <label class="toggle">
        <input type="checkbox" id="showpwd"> Show password
      </label>
      <button type="submit">Login</button>
    </form>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="foot">
      Back to <a href="../dashboard.php">Home</a>
    </div>
  </div>

<script>
  const cb = document.getElementById('showpwd');
  const pw = document.getElementById('password');
  cb.addEventListener('change', () => { pw.type = cb.checked ? 'text' : 'password'; });
  document.getElementById('username').focus();
</script>
</body>
</html>
