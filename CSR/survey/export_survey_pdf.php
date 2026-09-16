<?php
require __DIR__ . "/../../db_connect.php";


/*
    EXPORT PDF USING PRINT VIEW
    ---------------------------------
    Instead of using mPDF (which requires vendor/autoload.php),
    this export redirects the user to print_survey.php and triggers
    the browser's native "Save as PDF" feature.
*/

/* Pass all active filters (search, district, date range, sort, dir, etc.) */
$queryString = http_build_query($_GET);

/* Redirect to print view with auto-print enabled */
header("Location: print_survey.php?$queryString&auto=1");
exit;
