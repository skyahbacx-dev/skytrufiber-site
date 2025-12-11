<?php
/* --------------------------------------------
   ROUTER + URL ENCRYPTION SYSTEM (Railway Safe)
----------------------------------------------*/

// IMPORTANT: change this key → long, random, private
define("ENC_KEY", "REPLACE_WITH_A_32_CHAR_SECRET_KEY");

/* Encrypt path */
function encrypt_path($path) {
    return urlencode(
        base64_encode(
            openssl_encrypt($path, 'AES-256-ECB', ENC_KEY)
        )
    );
}

/* Decrypt path */
function decrypt_path($hash) {
    $decoded = base64_decode($hash);
    return openssl_decrypt($decoded, 'AES-256-ECB', ENC_KEY);
}

/* If page encoded exists */
if (isset($_GET['p'])) {
    $page = decrypt_path($_GET['p']);

    if ($page && file_exists($page)) {
        require $page;
        exit;
    }

    http_response_code(404);
    echo "404 Page Not Found";
    exit;
}

// DEFAULT HOME PAGE
echo "<h2>Welcome to AHB A Devt</h2>";
