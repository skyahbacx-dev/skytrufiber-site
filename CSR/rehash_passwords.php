<?php
include 'db_connect.php'; // Your PDO connection

// Fetch all CSR users
$stmt = $conn->query("SELECT id, username, password FROM csr_users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $old_pass = $user['password'];

    // Skip if already password_hash()
    if (password_get_info($old_pass)['algo'] !== 0) {
        echo "Skipping {$user['username']} (already hashed)\n";
        continue;
    }

    // If it’s MD5 hash, use dummy original (you may already know the plain text)
    if (strlen($old_pass) === 32 && ctype_xdigit($old_pass)) {
        $plain = '1234'; // ⚠️ Replace with the actual known password
    } else {
        $plain = $old_pass; // treat as plain text
    }

    $new_hash = password_hash($plain, PASSWORD_DEFAULT);

    $upd = $conn->prepare("UPDATE csr_users SET password = ? WHERE id = ?");
    $upd->execute([$new_hash, $user['id']]);

    echo "✅ Updated {$user['username']} with a secure hash.\n";
}
?>
