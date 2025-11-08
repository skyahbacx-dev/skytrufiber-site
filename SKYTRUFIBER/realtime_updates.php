<?php
include '../db_connect.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
ignore_user_abort(true);

/**
 * SSE: Sends a small update every 2 seconds if data changes
 * Works with PDO + PostgreSQL (Neon)
 */

$last_check = time();

while (true) {
    // Stop loop if browser disconnects
    if (connection_aborted()) break;

    try {
        // Get latest updated_at timestamps from both tables
        $chatStmt = $conn->query("SELECT MAX(updated_at) AS chat_last FROM chat");
        $chat_last = $chatStmt->fetch(PDO::FETCH_ASSOC)['chat_last'] ?? null;

        $clientStmt = $conn->query("SELECT MAX(updated_at) AS client_last FROM clients");
        $client_last = $clientStmt->fetch(PDO::FETCH_ASSOC)['client_last'] ?? null;

        // Convert timestamps to UNIX time safely
        $chat_time   = $chat_last   ? strtotime($chat_last)   : 0;
        $client_time = $client_last ? strtotime($client_last) : 0;

        $latest = max($chat_time, $client_time);

        // Push event if something updated
        if ($latest > $last_check) {
            echo "event: update\n";
            echo 'data: {"message":"new data"}' . "\n\n";
            ob_flush();
            flush();
            $last_check = $latest;
        }

        // Wait 2 seconds before next check
        sleep(2);
    } catch (PDOException $e) {
        echo "event: error\n";
        echo 'data: {"error":"' . addslashes($e->getMessage()) . '"}' . "\n\n";
        ob_flush();
        flush();
        break;
    }
}
?>
