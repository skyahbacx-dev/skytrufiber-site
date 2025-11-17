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
            // Fetch CSR user data including fullname
            $stmt = $conn->prepare("
                SELECT username, full_name, password, status 
                FROM csr_users 
                WHERE username = :username 
                LIMIT 1
            ");
            $stmt->execute([':username' => $u]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if (strtolower($row['status']) !== 'active') {
                    $error = "Account is not active.";
                } else {
                    $db_hash = $row['password'];
                    $ok = false;
                    $is_md5 = false;

                    // Check password (bcrypt/argon2 or md5 fallback)
                    if (password_verify($pw_input, $db_hash)) {
                        $ok = true;
                    } elseif (strlen($db_hash) === 32 && ctype_xdigit($db_hash) && md5($pw_input) === strtolower($db_hash)) {
                        $ok = true;
                        $is_md5 = true;
                    }

                    if ($ok) {
                        if ($is_md5) {
                            $new_hash = password_hash($pw_input, PASSWORD_DEFAULT);
                            $update_pw = $conn->prepare("UPDATE csr_users SET password = :new_hash WHERE username = :username");
                            $update_pw->execute([':new_hash' => $new_hash, ':username' => $u]);
                        }

                        session_regenerate_id(true);

                        // Save session details
                        $_SESSION['csr_user'] = $row['username'];
                        $_SESSION['csr_fullname'] = $row['full_name'];   // Important!!

                        // Track last login time
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
  html, body { height: 100%; margin: 0; padding: 0; font-family: "Segoe UI", Arial, sans-serif; }
  body {
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(to right, #c8f8c8, #e7ffe7);
  }

  .box {
    background: #fff;
    padding: 28px;
    border-radius: 14px;
    box-shadow: 0 10px 24px rgba(0,0,0,.12);
    width: 340px;
    text-align: center;
  }

  .box h2 { color: #066d06; margin-bottom: 16px; font-weight: 800; }

  .field { text-align: left; margin-bottom: 12px; }
  .field label { display: block; font-size: 12px; color: #055b05; margin-bottom: 6px; font-weight: 700; }

  input[type="text"], input[type="password"] {
    width: 100%; padding: 11px 5px; border-radius: 10px; border: 1px solid #cfd8cf; font-size: 14px;
  }

  button[type="submit"] {
    width: 100%; padding: 11px; border-radius: 10px; border: none;
    background: var(--green); color: #fff; font-weight: 800; cursor: pointer;
  }

  .error {
    color: #c62828; background: #ffebee; border: 1px solid #ffcdd2;
    padding: 8px; border-radius: 8px; margin-top: 12px; font-size: 13px;
  }
</style>
</head>
<body>

<div class="box">
  <h2>CSR Login</h2>
  <form method="POST">
    <div class="field">
      <label for="username">Username</label>
      <input id="username" type="text" name="username" required>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>
    </div>

    <button type="submit">Login</button>
  </form>

  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
</div>

</body>
</html>
