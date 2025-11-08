<?php
include '../db_connect.php'; // ✅ uses PDO

// List of users and their known plaintext passwords
$passwords = [
  'CSR1' => '1234',
  'CSR2' => '1234',
  'CSR3' => '1234',
  'CSR4' => '1234'
];

foreach ($passwords as $user => $plain) {
    $new_hash = password_hash($plain, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE csr_users SET password = :hash WHERE username = :user");
    $stmt->execute([':hash' => $new_hash, ':user' => $user]);

    echo "✅ Updated {$user} successfully.<br>";
}
?>
