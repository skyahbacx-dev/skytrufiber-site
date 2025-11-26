<?php
session_start();
include "../db_connect.php";

// Use correct session key
$csr = $_SESSION["csr_username"] ?? $_SESSION["csr_user"] ?? null;

if (!$csr) {
    http_response_code(401);
    exit("Unauthorized: CSR session missing");
}

$search = $_GET["search"] ?? "";

$sql = "
SELECT
    u.id,
    u.full_name,
    u.district,
    u.barangay,
    u.assigned_csr,
    (SELECT COUNT(*) FROM chat c WHERE c.client_id = u.id AND c.sender_type='client' AND c.seen=false) AS unread
FROM users u
";

if ($search !== "") $sql .= " WHERE LOWER(u.full_name) LIKE LOWER(:search)";
$sql .= " ORDER BY unread DESC, full_name ASC";

$stmt = $conn->prepare($sql);
($search !== "") ? $stmt->execute([":search" => "%$search%"]) : $stmt->execute();

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $lock = ($r["assigned_csr"] && $r["assigned_csr"] !== $csr)
        ? "<i class='fa-solid fa-lock' style='color:red;margin-left:6px'></i>"
        : "";

    echo "
    <div class='client-item' id='client-{$r['id']}' onclick='selectClient({$r['id']}, \"{$r['full_name']}\")'>
        <img src='upload/default-avatar.png' class='client-avatar'>
        <div class='client-content'>
            <div class='client-name'>{$r['full_name']} {$lock}
                " . ($r['unread'] > 0 ? "<span class='badge'>{$r['unread']}</span>" : "") . "
            </div>
            <div class='client-sub'>{$r['district']} • {$r['barangay']}</div>
        </div>
    </div>";
}
?>
