<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    die("Unauthorized");
}

require "../../db_connect.php";
require "../../fpdf186/fpdf.php";  // <-- make sure FPDF folder exists

$csrUser = $_SESSION["csr_user"];

// ===============================================
// FETCH ALL TICKETS FROM CLIENTS ASSIGNED TO CSR
// ===============================================
$stmt = $conn->prepare("
    SELECT 
        t.id AS ticket_id,
        t.status,
        t.client_id,
        u.full_name
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE u.assigned_csr = ?
    ORDER BY t.id ASC
");
$stmt->execute([$csrUser]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================================
// COUNT SUMMARY
// ===============================================
$summary = [
    "unresolved" => 0,
    "pending"    => 0,
    "resolved"   => 0
];

foreach ($tickets as $t) {
    $status = strtolower($t["status"]);
    if (isset($summary[$status])) {
        $summary[$status]++;
    }
}

// ===============================================
// PDF GENERATION
// ===============================================
$pdf = new FPDF();
$pdf->AddPage();

// AHBA LOGO -------------------------------------------------
$pdf->Image('../../AHBALOGO.png', 10, 8, 22);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40);
$pdf->Cell(120, 10, "Ticket Summary Report", 0, 1, "C");

$pdf->Ln(5);

// SUMMARY TABLE ----------------------------------------------
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 8, "SUMMARY", 0, 1);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(60, 8, "Unresolved: " . $summary["unresolved"], 0, 1);
$pdf->Cell(60, 8, "Pending:    " . $summary["pending"], 0, 1);
$pdf->Cell(60, 8, "Resolved:   " . $summary["resolved"], 0, 1);

$pdf->Ln(5);

// TICKET TABLE ----------------------------------------------
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 10, "Ticket #", 1);
$pdf->Cell(70, 10, "Client Name", 1);
$pdf->Cell(40, 10, "Status", 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 11);

foreach ($tickets as $row) {
    $pdf->Cell(30, 8, "#" . $row["ticket_id"], 1);
    $pdf->Cell(70, 8, utf8_decode($row["full_name"]), 1);
    $pdf->Cell(40, 8, strtoupper($row["status"]), 1);
    $pdf->Ln();
}

$pdf->Output("D", "Ticket_Summary.pdf");
exit;
?>
