<?php
require "index.php"; // to reuse encrypt function

$links = [
    "CSR Dashboard"   => "CSR/dashboard/csr_dashboard.php",
    "Main Dashboard"  => "dashboard/dashboard.php",
    "SkyTruFiber"     => "SKYTRUFIBER/skytrufiber.php"
];

foreach ($links as $name => $path) {
    $encrypted = encrypt_path($path);
    echo "<p>$name → <a href='/?p=$encrypted'>/?p=$encrypted</a></p>";
}
