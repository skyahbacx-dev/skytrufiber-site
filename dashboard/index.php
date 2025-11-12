<?php
$request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($request === '') $request = 'dashboard';

// Attempt to map URL to folder/file
$paths = [
    "CSR/$request.php",
    "SKYTRUFIBER/$request.php",
    "dashboard/$request.php"
];

foreach ($paths as $p) {
    $full = __DIR__ . '/' . $p;
    if (file_exists($full)) {
        include $full;
        exit;
    }
}

http_response_code(404);
echo "<h1>404 Not Found</h1><p>Requested page <code>/$request</code> does not exist.</p>";
