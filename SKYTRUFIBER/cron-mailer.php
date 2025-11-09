<?php
include "mail.php";
include "../db_connect.php";

date_default_timezone_set("Asia/Manila");

// Get pending reminders
$sql = "
    SELECT r.id, r.client_id, r.reminder_type, u.email, u.full_name
    FROM reminders r
    JOIN users u ON u.account_number = (
        SELECT account_number
        FROM clients c
        WHERE c.id = r.client_id
        LIMIT 1
    )
    WHERE r.status = 'pending'
";
$stmt = $conn->query($sql);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($reminders as $r) {

    $sent = sendReminderEmail($r['email'], $r['full_name'], $r['reminder_type']);

    if ($sent) {
        // Update as sent
        $update = $conn->prepare("
            UPDATE reminders
            SET status = 'sent', sent_at = NOW()
            WHERE id = :id
        ");
        $update->execute([':id' => $r['id']]);
    }
}
?>
