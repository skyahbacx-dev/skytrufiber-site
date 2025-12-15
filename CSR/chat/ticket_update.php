<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");

$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$status   = strtolower($_POST["status"] ?? "");
$csrUser  = $_SESSION["csr_user"] ?? null;

if (!$csrUser) { echo json_encode(["ok"=>false,"msg"=>"UNAUTHORIZED"]); exit; }
if (!$clientID || !$ticketID || !$status) { echo json_encode(["ok"=>false,"msg"=>"MISSING_DATA"]); exit; }

$validStatuses = ["unresolved", "pending", "resolved"];
if (!in_array($status, $validStatuses)) {
    echo json_encode(["ok"=>false,"msg"=>"INVALID_STATUS"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        t.status AS ticket_status,
        t.client_id,
        u.assigned_csr
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) { echo json_encode(["ok"=>false,"msg"=>"TICKET_NOT_FOUND"]); exit; }

$currentStatus = strtolower($ticket["ticket_status"]);
$assignedCSR   = $ticket["assigned_csr"];
$dbClientID    = intval($ticket["client_id"]);

if ($dbClientID !== $clientID) {
    echo json_encode(["ok"=>false,"msg"=>"CLIENT_TICKET_MISMATCH"]);
    exit;
}

if ($assignedCSR !== $csrUser) {
    echo json_encode(["ok"=>false,"msg"=>"NOT_ASSIGNED"]);
    exit;
}

if ($currentStatus === "resolved") {
    echo json_encode(["ok"=>false,"msg"=>"ALREADY_RESOLVED"]);
    exit;
}

if ($currentStatus !== $status) {

    $update = $conn->prepare("UPDATE tickets SET status = :s WHERE id = :tid");
    $update->execute([":s"=>$status, ":tid"=>$ticketID]);

    $log = $conn->prepare("
        INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
        VALUES (:tid, :cid, :csr, :action, NOW())
    ");
    $log->execute([
        ":tid"=>$ticketID,
        ":cid"=>$clientID,
        ":csr"=>$csrUser,
        ":action"=>$status
    ]);
}

if ($status === "resolved") {
    $unlock = $conn->prepare("
        UPDATE users
        SET is_locked = FALSE,
            ticket_lock = FALSE,
            transfer_request = NULL
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}

echo "OK";
exit;

