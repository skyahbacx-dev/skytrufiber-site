<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$username = $_SESSION['csr_user'];

// Fetch CSR details
$stmt = $conn->prepare("SELECT id, full_name, email, avatar FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Error: CSR not found.");
}

$msg = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $new_name = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);

    // Optional password update
    $new_password = trim($_POST['password']);
    $password_sql = "";

    if ($new_password !== "") {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $password_sql = ", password = '$hashed'";
    }

    // Avatar upload
    $avatar_file = $user['avatar'];  // keep old avatar unless uploading new

    if (!empty($_FILES['avatar']['name'])) {

        $allowed = ["image/png", "image/jpeg"];
        if (in_array($_FILES['avatar']['type'], $allowed)) {

            if (!is_dir("uploads")) {
                mkdir("uploads", 0777, true);
            }

            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = "avatar_" . $user['id'] . "." . $ext;
            $path = "uploads/" . $new_filename;

            move_uploaded_file($_FILES['avatar']['tmp_name'], $path);

            $avatar_file = $new_filename;
        }
    }

    // Update DB
    $u = $conn->prepare("
        UPDATE csr_users SET 
            full_name = :n,
            email = :e,
            avatar = :a
            $password_sql
        WHERE username = :u
    ");

    $u->execute([
        ':n' => $new_name,
        ':e' => $new_email,
        ':a' => $avatar_file,
        ':u' => $username
    ]);

    $msg = "Profile updated successfully!";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        .container { width: 500px; margin: auto; background: #fff; padding: 20px; border-radius: 12px; 
                     box-shadow: 0 2px 10px rgba(0,0,0,0.1);}
        h2 { margin-top: 0; }
        input, button { width: 100%; padding: 10px; margin-top: 8px; border-radius: 8px; border: 1px solid #ccc; }
        button { background: #0aa05b; color: #fff; border: none; font-weight: bold; cursor: pointer; }
        button:hover { background: #078149; }
        .msg { background: #e7ffe7; padding: 10px; border-left: 4px solid #0aa05b; margin-bottom: 15px; }
        img.avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Profile</h2>

    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>New Password (optional)</label>
        <input type="password" name="password" placeholder="Leave blank to keep current password">

        <label>Avatar</label><br>
        <?php if ($user['avatar']): ?>
            <img src="uploads/<?= $user['avatar'] ?>" class="avatar">
        <?php else: ?>
            <img src="../default.png" class="avatar">
        <?php endif; ?>
        <input type="file" name="avatar" accept="image/png, image/jpeg">

        <button type="submit">Save Changes</button>
    </form>
</div>

</body>
</html>
