<?php
if (getenv('RENDER') === 'true') {
    // Production (Render → Neon)
    require __DIR__ . '/db_connect_neon.php';
} else {
    // Local development (XAMPP → MySQL)
    require __DIR__ . '/db_connect_mysql.php';
}
