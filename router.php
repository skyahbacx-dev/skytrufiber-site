<?php
// Public clean paths for the AHBA marketing one-page site.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

// Let PHP's built-in server serve real static assets (CSS, JS, images, fonts).
// Without this, requests for assets are incorrectly sent through index.php.
$staticFile = __DIR__ . $path;
if (
    $path !== '/' &&
    is_file($staticFile) &&
    preg_match('/\.(?:css|js|mjs|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf|map)$/i', $path)
) {
    return false;
}

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
