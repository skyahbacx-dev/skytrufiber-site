<?php

$DB_DRIVER = getenv('DB_DRIVER') ?: 'mysql';

if ($DB_DRIVER === 'pgsql') {
    require __DIR__ . '/db_connect_neon.php';
} else {
    require __DIR__ . '/db_connect_mysql.php';
}
