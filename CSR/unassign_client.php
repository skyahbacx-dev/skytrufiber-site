<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$csrUser   = $_SESSION["csr_user"];
$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    http_response_code(400);
    exit("Missing client_id");
}

try {
    // Only unassign if the logged-in CSR owns the client
    $stmt = $conn->prepare("
        UPDATE users 
        SET assigned_csr = NULL
        WHERE id = :id AND assigned_csr = :csr
    ");
    $stmt->execute([
        ":csr" => $csrUser,
        ":id"  => $client_id
    ]);

    echo "success";
} catch (PDOException $e) {
    http_response_code(500);
    echo $e->getMessage();
}
