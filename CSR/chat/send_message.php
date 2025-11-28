<?php
// DEBUG MODE: capture ANY output issues early
ob_start();
if (!isset($_SESSION)) session_start();

// IMPORTANT: OUTPUT JSON ALWAYS
header("Content-Type: application/json; charset=utf-8");

$debugFile = __DIR__ . "/debug_send.log";

// Log session + POST data immediately
file_put_contents($debugFile,
    "---- REQUEST ---- " . date("Y-m-d H:i:s") . "\n" .
    "POST: " . json_encode($_POST) . "\n" .
    "SESSION: " . json_encode($_SESSION) . "\n\n",
    FILE_APPEND
);

require_once "../../db_connect.php";

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;
$message  = isset($_POST["message"]) ? trim($_POST["message"]) : "";

// Validate
if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "NO_SESSION"]);
    exit;
}

if (!$clientID || $message === "") {
    echo json_encode(["status" => "error", "msg" => "MISSING_DATA"]);
    exit;
}

try {
    // Insert
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', ?, 0, 0, NOW())
    ");
    $stmt->execute([$clientID, $message]);

    echo json_encode(["status" => "ok"]);

} catch (Throwable $e) {

    // Log failure
    file_put_contents($debugFile,
        "ERROR: " . $e->getMessage() . "\n\n",
        FILE_APPEND
    );

    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}

// Flush buffer safely
ob_end_flush();
exit;
