<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

require_once "../../db_connect.php";

echo "DEBUG2 OK<br>";

file_put_contents(__DIR__ . "/php_errors.log", "--- TEST RUN AT " . date("Y-m-d H:i:s") . " ---\n", FILE_APPEND);

// Force a test error
undefined_function_test();

/*
 If hosting blocks error display,
 the error will be written inside php_errors.log
*/
