<?php
if (!isset($_SESSION)) session_start();
require __DIR__ . "/../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$message  = trim($_POST["message"] ?? "");

if (!$csrUser)
    exit(json_encode(["status" => "error", "msg" => "CSR not logged in"]));

if ($clientID <= 0 || $ticketID <= 0)
    exit(json_encode(["status" => "error", "msg" => "Invalid client or ticket"]));

if ($message === "")
    exit(json_encode(["status" => "error", "msg" => "Message cannot be empty"]));

try {
    $stmt = $conn->prepare("
        SELECT 
            t.status AS ticket_status,
            t.client_id,
            u.assigned_csr
        FROM tickets t
        JOIN users u ON u.id = t.client_id
        WHERE t.id = :tid
        LIMIT 1
    ");
    $stmt->execute([":tid" => $ticketID]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket)
        exit(json_encode(["status" => "error", "msg" => "Ticket not found"]));

    if ($ticket["client_id"] != $clientID)
        exit(json_encode(["status" => "error", "msg" => "Ticket does not belong to this client"]));

    if (strtolower($ticket["ticket_status"]) === "resolved")
        exit(json_encode(["status" => "blocked", "msg" => "Ticket resolved — messaging disabled."]));

    if ($ticket["assigned_csr"] !== $csrUser)
        exit(json_encode(["status" => "locked", "msg" => "You are not assigned to this ticket."]));

    /* INSERT MESSAGE */
    $insert = $conn->prepare("
        INSERT INTO chat (
            ticket_id, client_id, sender_type,
            message, deleted, edited, delivered, seen, created_at
        )
        VALUES (:tid, :cid, 'csr', :msg, 0, 0, 1, 0, NOW())
    ");

    $insert->execute([
        ":tid" => $ticketID,
        ":cid" => $clientID,
        ":msg" => $message
    ]);

    echo json_encode(["status" => "ok", "msg" => "Message sent"]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "msg" => "Server error: " . $e->getMessage()
    ]);
    exit;
}
?>
