<?php
include "../db_connect.php";
session_start();

$csr = $_SESSION["csr_user"] ?? "";
$search = $_GET["search"] ?? "";

$sql = "
SELECT
    u.id,
    u.full_name,
    u.email,
    u.district,
    u.barangay,
    u.assigned_csr,
    u.is_online,
    (
        SELECT COUNT(*) FROM chat c
        WHERE c.client_id = u.id AND c.sender_type = 'client'
        AND c.id NOT IN (SELECT chat_id FROM chat_read WHERE client_id = u.id AND csr = :csr)
    ) AS unread_count
FROM users u
WHERE u.full_name ILIKE :s OR u.email ILIKE :s
ORDER BY unread_count DESC, u.full_name ASC;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":csr" => $csr,
    ":s" => "%$search%"
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $active = $r["assigned_csr"] === $csr ? "assigned-me" : "";
    $lock = $r["assigned_csr"] && $r["assigned_csr"] !== $csr;

    $icon = $lock
        ? "<i class='fa-solid fa-lock lock-icon'></i>"
        : "<i class='fa-solid fa-comment'></i>";

    echo "
    <div class='client-item $active' id='client-{$r["id"]}' onclick='selectClient({$r["id"]}, \"{$r["full_name"]}\")'>
        <div class='client-left'>
            <div class='avatar-small'>".strtoupper($r["full_name"][0])."</div>
            <div>
                <div class='client-name'>{$r["full_name"]}</div>
                <div class='client-email'>{$r["email"]}</div>
            </div>
        </div>

        <div class='client-right'>
            ".($r["unread_count"] > 0 ? "<span class='badge'>{$r["unread_count"]}</span>" : "")."
            $icon
        </div>
    </div>";
}
?>
