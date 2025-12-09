<?php
session_start();
include '../db_connect.php';

$message = '';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'], $_POST['password'])) {

    $input    = trim($_POST['full_name']);
    $password = $_POST['password'];
    $concern  = trim($_POST['concern'] ?? '');

    if ($input && $password) {
        try {

            // Fetch user by email OR full_name
            $stmt = $conn->prepare("
                SELECT *
                FROM users
                WHERE email = :input OR full_name = :input
                ORDER BY id ASC
                LIMIT 1
            ");
            $stmt->execute([':input' => $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                // Check latest ticket
                $ticketStmt = $conn->prepare("
                    SELECT id, status
                    FROM tickets
                    WHERE client_id = :cid
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $ticketStmt->execute([':cid' => $user['id']]);
                $lastTicket = $ticketStmt->fetch(PDO::FETCH_ASSOC);

                if (!$lastTicket || $lastTicket['status'] === 'resolved') {

                    // Create new ticket
                    $newTicket = $conn->prepare("
                        INSERT INTO tickets (client_id, status, created_at)
                        VALUES (:cid, 'unresolved', NOW())
                    ");
                    $newTicket->execute([':cid' => $user['id']]);
                    $ticketId = $conn->lastInsertId();

                } else {
                    $ticketId = $lastTicket['id'];
                }

                // Session variables
                $_SESSION['client_id']   = $user['id'];
                $_SESSION['client_name'] = $user['full_name'];
                $_SESSION['email']       = $user['email'];
                $_SESSION['ticket_id']   = $ticketId;

                // Check if chat already has messages
                $checkMsgs = $conn->prepare("
                    SELECT COUNT(*) FROM chat
                    WHERE ticket_id = :tid
                ");
                $checkMsgs->execute([':tid' => $ticketId]);
                $hasMessages = ($checkMsgs->fetchColumn() > 0);

                // ----------------------------------------------
                // INSERT CSR GREETING FIRST IF CHAT IS EMPTY
                // ----------------------------------------------
                if (!$hasMessages) {

                    $autoGreet = $conn->prepare("
                        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
                        VALUES (:tid, :cid, 'csr', 'Good day! How may we assist you today?', TRUE, FALSE, NOW())
                    ");
                    $autoGreet->execute([
                        ':tid' => $ticketId,
                        ':cid' => $user['id']
                    ]);
                }

                // ----------------------------------------------
                // INSERT CLIENT CONCERN AFTER CSR GREETING
                // ----------------------------------------------
                if (!empty($concern)) {

                    $insert = $conn->prepare("
                        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
                        VALUES (:tid, :cid, 'client', :msg, TRUE, FALSE, NOW())
                    ");

                    $insert->execute([
                        ':tid' => $ticketId,
                        ':cid' => $user['id'],
                        ':msg' => $concern
                    ]);

                    // Flag suggestion bubble for JS
                    $_SESSION['show_suggestions'] = true;
                }

                // Redirect to chat
                header("Location: chat/chat_support.php?ticket=" . $ticketId);
                exit;

            } else {
                $message = "❌ Invalid email/full name or password.";
            }

        } catch (PDOException $e) {
            $message = "⚠ Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "⚠ Please fill in all fields.";
    }
}
?>
