<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$message  = trim($_POST["message"] ?? "");

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "CSR not logged in"]);
    exit;
}

if ($clientID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client"]);
    exit;
}

if ($message === "") {
    echo json_encode(["status" => "error", "msg" => "Message cannot be empty"]);
    exit;
}

try {

    /* ==========================================================
       1) Verify the CSR is assigned to this client
    ========================================================== */
    $stmt = $conn->prepare("
        SELECT assigned_csr, ticket_status
        FROM users
        WHERE id = :cid
        LIMIT 1
    ");
    $stmt->execute([":cid" => $clientID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["status" => "error", "msg" => "Client not found"]);
        exit;
    }

    $assignedCSR   = $user["assigned_csr"];
    $ticketStatus  = $user["ticket_status"] ?? "unresolved";

    /* ==========================================================
       2) Block CSR from messaging a resolved ticket
    ========================================================== */
    if ($ticketStatus === "resolved") {
        echo json_encode([
            "status" => "blocked",
            "msg"    => "This ticket is already resolved. Messaging disabled."
        ]);
        exit;
    }

    /* ==========================================================
       3) Enforce lock — CSR may only message assigned clients
    ========================================================== */
    if ($assignedCSR !== $csrUser) {
        echo json_encode([
            "status" => "locked",
            "msg"    => "You are not assigned to this client."
        ]);
        exit;
    }

    /* ==========================================================
       4) Insert CSR message
          Delivered = TRUE so the client instantly receives it
    ========================================================== */
    $insert = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, deleted, edited, delivered, seen, created_at)
        VALUES (:cid, 'csr', :msg, FALSE, FALSE, TRUE, FALSE, NOW())
    ");

    $insert->execute([
        ":cid" => $clientID,
        ":msg" => $message
    ]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Message sent"
    ]);
    exit;

} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "msg"    => "Server error: " . $e->getMessage()
    ]);
    exit;
}
?>
