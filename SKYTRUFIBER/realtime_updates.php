<?php
include '../db_connect.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
ignore_user_abort(true);

/**
 * SSE connection stays open; this script sends updates every 2 seconds.
 * The browser auto-refreshes data whenever new DB updates are detected.
 */

$last_check = time();

while (true) {
    // If client disconnected, stop
    if (connection_aborted()) break;

    // Check the latest change timestamps from `chat` and `clients`
    $chat_result = $conn->query("SELECT MAX(updated_at) AS chat_last FROM chat");
    $client_result = $conn->query("SELECT MAX(updated_at) AS client_last FROM clients");

    $chat_last = $chat_result && $chat_result->num_rows ? $chat_result->fetch_assoc()['chat_last'] : null;
    $client_last = $client_result && $client_result->num_rows ? $client_result->fetch_assoc()['client_last'] : null;

    $latest = max(strtotime($chat_last ?? 0), strtotime($client_last ?? 0));

    // If something updated since last check, push event
    if ($latest > $last_check) {
        echo "event: update\n";
        echo 'data: {"message":"new data"}' . "\n\n";
        ob_flush();
        flush();
        $last_check = $latest;
    }

    // Wait 2 seconds before checking again
    sleep(2);
}
?>
