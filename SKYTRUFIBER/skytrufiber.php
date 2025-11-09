<?php
session_start();
include '../db_connect.php'; // make sure your PDO connection is correct

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $concern = trim($_POST['concern'] ?? '');

    if ($email_or_name && $password) {
        try {
            // ✅ 1. Validate user login
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :input OR full_name = :input LIMIT 1");
            $stmt->execute([':input' => $email_or_name]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // ✅ 2. Store session
                $_SESSION['user'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];

                // ✅ 3. Check if client exists or create
                $clientStmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :name LIMIT 1");
                $clientStmt->execute([':name' => $user['full_name']]);
                $client = $clientStmt->fetch(PDO::FETCH_ASSOC);

                if (!$client) {
                    $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at) VALUES (:n, 'Unassigned', NOW())")
                         ->execute([':n' => $user['full_name']]);
                    $client_id = $conn->lastInsertId();
                    $assigned_csr = 'Unassigned';
                } else {
                    $client_id = $client['id'];
                    $assigned_csr = $client['assigned_csr'];
                }

                // ✅ 4. Pick a random available CSR
                $csrStmt = $conn->query("SELECT username, full_name FROM csr_users WHERE is_online = TRUE ORDER BY RANDOM() LIMIT 1");
                $csr = $csrStmt->fetch(PDO::FETCH_ASSOC);

                if ($csr) {
                    $csr_user = $csr['username'];
                    $csr_fullname = $csr['full_name'];
                } else {
                    $csr_user = 'Unassigned';
                    $csr_fullname = '';
                }

                // ✅ 5. Update assigned CSR in clients table
                $updateClient = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :cid");
                $updateClient->execute([':csr' => $csr_user, ':cid' => $client_id]);

                // ✅ 6. Save the customer's concern as the first chat message
                if (!empty($concern)) {
                    $stmtChat = $conn->prepare("
                        INSERT INTO chat (client_id, sender_type, message, created_at)
                        VALUES (:cid, 'client', :msg, NOW())
                    ");
                    $stmtChat->execute([
                        ':cid' => $client_id,
                        ':msg' => $concern
                    ]);
                }

                // ✅ 7. Auto-send a CSR greeting
                if ($csr_user !== 'Unassigned') {
                    $greeting = "👋 Hi " . $user['full_name'] . "! This is " . $csr_fullname . " from SkyTruFiber. Thank you for reaching out! How can I assist you further?";
                    $stmtGreeting = $conn->prepare("
                        INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
                        VALUES (:cid, 'csr', :msg, :csr, :csr_full, NOW())
                    ");
                    $stmtGreeting->execute([
                        ':cid' => $client_id,
                        ':msg' => $greeting,
                        ':csr' => $csr_user,
                        ':csr_full' => $csr_fullname
                    ]);
                }

                // ✅ 8. Redirect to chat support page
                header("Location: chat_support.php?username=" . urlencode($user['full_name']));
                exit;

            } else {
                $message = "❌ Invalid email/full name or password.";
            }

        } catch (PDOException $e) {
            $message = "⚠️ Database error: " . htmlspecialchars($e->getMessage());
        }

    } else {
        $message = "⚠️ Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Service Portal - SkyTruFiber</title>
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  margin: 0;
}

.logo-container {
  text-align: center;
  margin-bottom: 15px;
}
.logo-container img {
  width: 140px;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

form {
  background: #fff;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  width: 380px;
}

h2 {
  color: #004466;
  text-align: center;
  margin-bottom: 15px;
}

label {
  font-weight: 600;
  color: #004466;
  display: block;
  margin-top: 10px;
}

input, textarea {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

textarea {
  height: 80px;
  resize: none;
}

button {
  width: 100%;
  padding: 10px;
  background: #0099cc;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  margin-top: 15px;
}

button:hover { background: #007a99; }

.show-pass {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  font-size: 13px;
  color: #004466;
}

.show-pass input[type="checkbox"] {
  width: 16px;
  height: 16px;
  margin: 0;
  vertical-align: middle;
}

.message { color: red; text-align: center; margin-top: 10px; }

a { color: #007744; text-decoration: none; }
a:hover { text-decoration: underline; }

@media (max-width:420px){
  form { width: 92%; padding: 18px; }
  .logo-container img { width: 120px; }
}
</style>
</head>
<body>

<div class="logo-container">
  <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</div>

<form method="POST">
  <h2>Customer Service Portal</h2>

  <label for="full_name">Email or Full Name:</label>
  <input type="text" id="full_name" name="full_name" placeholder="Enter your email or full name" required>

  <label for="password">Password:</label>
  <input type="password" id="password" name="password" placeholder="Enter password" required>

  <div class="show-pass">
    <label for="showPassword">Show Password</label>
    <input type="checkbox" id="showPassword">
  </div>

  <label for="concern">Your Concern / Inquiry:</label>
  <textarea id="concern" name="concern" placeholder="Describe your concern or issue here..." required></textarea>

  <button type="submit">Submit</button>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p style="text-align:center; margin-top:10px;">No account yet? <a href="register.php">Register here</a></p>
</form>

<script>
document.getElementById('showPassword').addEventListener('change', function() {
  const pw = document.getElementById('password');
  pw.type = this.checked ? 'text' : 'password';
});
</script>

</body>
</html>
