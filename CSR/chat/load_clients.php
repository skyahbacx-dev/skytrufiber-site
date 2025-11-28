<?php
require_once "../../db_connect.php";
session_start();

$csrUser = $_SESSION["csr_user"] ?? null;

try {
    $stmt = $conn->query("
        SELECT id, full_name, email, assigned_csr, is_locked, is_online
        FROM users
        ORDER BY id DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id    = $row['id'];
        $name  = htmlspecialchars($row['full_name']);
        $email = htmlspecialchars($row['email']);
        $locked = $row['is_locked'] ? "locked" : "";
        $online = $row['is_online'] ? "online-dot" : "offline-dot";

        echo "
        <div class='client-item' data-id='$id' data-name='$name'>
            <div class='client-info'>
                <span class='client-name'>$name</span>
                <span class='client-email'>$email</span>
            </div>
            <div class='client-actions'>
                <button class='btn-add' title='Assign Client'>&plus;</button>
                <button class='btn-remove' title='Remove'>&minus;</button>
                <button class='btn-lock $locked' title='Lock'>&#128274;</button>
            </div>
        </div>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>DB Error: " . $e->getMessage() . "</p>";
}
?>
