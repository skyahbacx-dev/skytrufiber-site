<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr = $_SESSION['csr_user'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);

    $stmt = $conn->prepare("UPDATE csr_users SET full_name=:n, email=:e WHERE username=:u");
    $stmt->execute([
        ":n"=>$name,
        ":e"=>$email,
        ":u"=>$csr
    ]);

    header("Location: csr_dashboard.php");
}

$stmt = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username=:u");
$stmt->execute([":u"=>$csr]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<form method="POST">
    <h2>Edit Profile</h2>
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($d['full_name']) ?>">

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($d['email']) ?>">

    <button>Save</button>
</form>
