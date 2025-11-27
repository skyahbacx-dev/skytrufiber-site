<?php
session_start();
require_once "../../db_connect.php";

if (!isset($_SESSION["csr_id"])) {
    echo "<p>No CSR specified.</p>";
    exit;
}

$csrID = $_SESSION["csr_id"];

try {
    $query = $conn->prepare("
        SELECT u.id, u.full_name, u.is_online
        FROM users u
        WHERE u.assigned_csr = :csrID
        ORDER BY u.full_name ASC
    ");
    $query->execute([":csrID" => $csrID]);

    if ($query->rowCount() === 0) {
        echo "<p>No assigned clients yet.</p>";
    } else {
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "
                <div class='client-item' onclick='openChat({$row['id']})'>
                    <span>{$row['full_name']}</span>
                    <small style='float:right; color:" . ($row['is_online'] ? "green" : "red") . "'>
                        ●
                    </small>
                </div>
            ";
        }
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
