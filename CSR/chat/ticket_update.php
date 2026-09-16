<?php
ini_set("session.name", "CSRSESSID");
if (!isset($_SESSION)) session_start();

header("Content-Type: application/json");
require "../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
if (!$csrUser) {
    echo json_encode(["ok" => false, "msg" => "SESSION_EXPIRED"]);
    exit;
}

$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$status   = strtolower($_POST["status"] ?? "");

$valid = ["unresolved", "pending", "resolved"];
if (!$clientID || !$ticketID || !in_array($status, $valid, true)) {
    echo json_encode(["ok" => false, "msg" => "INVALID_DATA"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT t.status, t.client_id, u.assigned_csr
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo json_encode(["ok" => false, "msg" => "TICKET_NOT_FOUND"]);
    exit;
}

$currentStatus = strtolower($ticket["status"]);
$assignedCSR   = $ticket["assigned_csr"];

/* ======================================================
   PERMISSION RULES (FIXED)
====================================================== */

// CSR allowed if:
// - assigned to ticket
// - OR ticket unassigned
// - OR reopening resolved ticket
if (
    $assignedCSR !== null &&
    $assignedCSR !== $csrUser &&
    $currentStatus !== "resolved"
) {
    echo json_encode(["ok" => false, "msg" => "NOT_ASSIGNED"]);
    exit;
}

/* ======================================================
   UPDATE STATUS
====================================================== */
if ($currentStatus !== $status) {

    $conn->prepare("
        UPDATE tickets SET status = ? WHERE id = ?
    ")->execute([$status, $ticketID]);

    $conn->prepare("
        INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
        VALUES (?, ?, ?, ?, NOW())
    ")->execute([
        $ticketID,
        $clientID,
        $csrUser,
        $currentStatus . "_to_" . $status
    ]);
}

/* ======================================================
   SIDE EFFECTS
====================================================== */
if ($status === "resolved") {
    $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL,
            ticket_lock = FALSE,
            is_locked = FALSE
        WHERE id = ?
    ")->execute([$clientID]);
} else {
    // auto-assign on reopen
    $conn->prepare("
        UPDATE users
        SET assigned_csr = ?, ticket_lock = TRUE, is_locked = TRUE
        WHERE id = ?
    ")->execute([$csrUser, $clientID]);
}

echo json_encode(["ok" => true]);
exit;
