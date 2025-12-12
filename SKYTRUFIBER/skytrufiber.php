<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$message = '';

/* ============================================================
   LOGIN HANDLER
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'], $_POST['password'])) {

    $input    = trim($_POST['full_name']);
    $password = $_POST['password'];
    $concern  = trim($_POST['concern_text'] ?? $_POST['concern_dropdown'] ?? '');

    if ($input && $password) {

        try {
            // Fetch user
            $stmt = $conn->prepare("
                SELECT *
                FROM users
                WHERE email = :input OR full_name = :input
                LIMIT 1
            ");
            $stmt->execute([':input' => $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                /* Fetch last ticket */
                $ticketStmt = $conn->prepare("
                    SELECT id, status
                    FROM tickets
                    WHERE client_id = :cid
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $ticketStmt->execute([':cid' => $user['id']]);
                $lastTicket = $ticketStmt->fetch(PDO::FETCH_ASSOC);

                /* Create new ticket if needed */
                if (!$lastTicket || $lastTicket['status'] === 'resolved') {

                    $newTicket = $conn->prepare("
                        INSERT INTO tickets (client_id, status, created_at)
                        VALUES (:cid, 'unresolved', NOW())
                    ");
                    $newTicket->execute([':cid' => $user['id']]);

                    $ticketId = $conn->lastInsertId();
                    $_SESSION['show_suggestions'] = true;

                } else {
                    $ticketId = $lastTicket['id'];
                }

                /* Save Session */
                $_SESSION['client_id'] = $user['id'];
                $_SESSION['ticket_id'] = $ticketId;

                /* Insert first client message */
                if (!empty($concern)) {
                    $insert = $conn->prepare("
                        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, created_at)
                        VALUES (:tid, :cid, 'client', :msg, TRUE, NOW())
                    ");
                    $insert->execute([
                        ':tid' => $ticketId,
                        ':cid' => $user['id'],
                        ':msg' => $concern
                    ]);
                }

                header("Location: /fiber/chat?ticket=$ticketId");
                exit;

            } else {
                $message = "❌ Invalid login credentials.";
            }

        } catch (PDOException $e) {
            $message = "⚠ Database error: " . htmlspecialchars($e->getMessage());
        }

    } else {
        $message = "⚠ Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Customer Portal</title>

<style>
body { 
    margin:0; 
    font-family:"Segoe UI", Arial, sans-serif; 
    background:linear-gradient(to bottom right, #cceeff, #e6f7ff);
    display:flex; 
    justify-content:center; 
    align-items:center; 
    min-height:100vh; 
}

.container {
    background:white;
    padding:32px;
    border-radius:20px;
    box-shadow:0 5px 18px rgba(0,0,0,0.18);
    width:380px;
    text-align:center;
}

.container img {
    width:150px;
    margin-bottom:15px;
}

input, select, textarea {
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
    box-sizing:border-box;
}

textarea { 
    height:80px; 
    resize:none; 
    display:none; 
}

button {
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
button:hover { background:#008c96; }

.small-links {
    margin-top:12px;
    font-size:14px;
}

.small-links a {
    color:#0077a3;
    text-decoration:none;
    margin:0 5px;
}
.small-links a:hover { text-decoration:underline; }

.message { 
    color:red; 
    font-size:0.9em; 
    margin-bottom:8px; 
}
</style>
</head>

<body>

<div class="container">

    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
    <h2>Customer Service Portal</h2>

<?php if ($message): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form id="loginForm" method="POST">

    <input type="text" name="full_name" placeholder="Email or Full Name" required>

    <input type="password" name="password" placeholder="Password" required>

    <!-- Concern dropdown -->
    <select name="concern_dropdown" id="concernSelect">
        <option value="">Select Concern / Inquiry</option>
        <option>Slow Internet</option>
        <option>No Connection</option>
        <option>Router LOS Light On</option>
        <option>Intermittent Internet</option>
        <option>Billing Concern</option>
        <option>Account Verification</option>
        <option>Installation Request</option>
        <option value="others">Others…</option>
    </select>

    <!-- Textarea appears only if "Others" is selected -->
    <textarea id="concernText" name="concern_text" placeholder="Type your concern here..."></textarea>

    <button type="submit">Submit</button>
</form>

<div class="small-links">
    <a href="/fiber/consent">Register here</a> |
    <a href="#" id="forgotLink">Forgot Password?</a>
</div>

</div>

<script>
const concernSelect = document.getElementById("concernSelect");
const concernText   = document.getElementById("concernText");

concernSelect.addEventListener("change", function () {
    if (this.value === "others") {
        concernText.style.display = "block";
    } else {
        concernText.style.display = "none";
        concernText.value = "";
    }
});
</script>

</body>
</html>
