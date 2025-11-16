<?php
include "../db_connect.php";

$client_id = $_GET["id"] ?? $_GET["client_id"] ?? 0;

$stmt = $conn->prepare("
    SELECT 
        c.name,
        c.district,
        c.barangay,
        c.assigned_csr,
        c.contact,
        u.email
    FROM clients c
    LEFT JOIN users u ON u.account_number = c.account_number
    WHERE c.id = :id
    LIMIT 1
");
$stmt->execute([":id" => $client_id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "name"       => $c["name"] ?? "",
    "email"      => $c["email"] ?? "No email",
    "district"   => $c["district"] ?? "",
    "barangay"   => $c["barangay"] ?? "",
    "assigned_csr" => $c["assigned_csr"] ?? "Unassigned",
    "phone"      => $c["contact"] ?? "Not available"
]);
?>
