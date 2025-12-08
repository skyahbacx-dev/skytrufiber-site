<?php
// This file can now act purely as an AJAX handler if needed, 
// but we are keeping all logic in the front-end using GitHub Actions.
// You can optionally leave this empty or return a JSON response.
header('Content-Type: application/json');
echo json_encode(['message' => 'This page is deprecated. Use skytrufiber.php forgot password form.']);
exit;
