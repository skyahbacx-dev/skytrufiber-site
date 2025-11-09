<?php
session_start();
include '../db_connect.php';

header("Content-Type: application/json");

if (!isset($_SESSION['csr_user'])) {
    echo json_encode(['status'=>'auth_error']);
    exit;
}

$csr_user = $_SESSION['csr_user'];

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = trim($_POST['password'] ?? '');

if ($full_name === "" || $email === "") {
    echo json_encode(['status'=>'empty_fields']);
    exit;
}

try {
    // Prepare update
    if ($password !== "") {
        $hashPass = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE csr_users SET full_name = :fn, email = :email, password = :pw WHERE username = :u";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':fn' => $full_name,
            ':email' => $email,
            ':pw' => $hashPass,
            ':u' => $csr_user
        ]);
    } else {
        $sql = "UPDATE csr_users SET full_name = :fn, email = :email WHERE username = :u";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':fn' => $full_name,
            ':email' => $email,
            ':u' => $csr_user
        ]);
    }

    echo json_encode(['status'=>'ok']);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
}
?>
