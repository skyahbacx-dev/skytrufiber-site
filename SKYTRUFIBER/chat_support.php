<?php
session_start();
if (!isset($_SESSION['name'])) {
    header("Location: skytrufiber.php");
    exit;
}

$name = $_SESSION['name'];
$concern = $_SESSION['concern'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Chat Support</title>
<style>
body { font-family: Arial; background: #e8fff0; text-align: center; padding: 50px; }
.chatbox { background: #fff; border-radius: 10px; padding: 20px; width: 400px; margin: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
</style>
</head>
<body>

<div class="chatbox">
  <h2>Welcome, <?= htmlspecialchars($name) ?> 👋</h2>
  <p><strong>Your concern:</strong> <?= htmlspecialchars($concern) ?></p>
  <hr>
  <p>Connecting you to a CSR agent...</p>
  <!-- You can integrate your live chat script here -->
</div>

</body>
</html>
