<?php
// Public clean paths for the AHBA marketing one-page site.
// Existing static assets are served by PHP's built-in server.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

$sections = [
    '/' => 'home',
    '/services' => 'services',
    '/about' => 'about',
    '/management' => 'management',
    '/contact' => 'contact'
];

if (isset($sections[$path])) {
    if ($path !== '/') {
        $GLOBALS['AHBA_PUBLIC_SECTION'] = $sections[$path];
    }
    require __DIR__ . '/dashboard/dashboard.php';
    return true;
}

// Preserve all existing /fiber registration, consent and chat routing.
require __DIR__ . '/index.php';
return true;
