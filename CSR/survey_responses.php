<?php
include '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $client = trim($_POST['client_name']);
    $account = trim($_POST['account_number']);
    $district = trim($_POST['district']);
    $barangay = trim($_POST['location']);
    $feedback = trim($_POST['feedback']);

    if ($client && $account && $district && $barangay && $feedback) {
        try {

            $stmt = $conn->prepare("
                INSERT INTO survey_responses (client_name, account_number, district, location, feedback, created_at)
                VALUES (:client, :acc, :district, :loc, :fb, NOW())
            ");

            $stmt->execute([
                ':client' => $client,
                ':acc' => $account,
                ':district' => $district,
                ':loc' => $barangay,
                ':fb' => $feedback
            ]);

            echo "<script>alert('✅ Survey submitted!'); window.location='survey_form.php';</script>";

        } catch (PDOException $e) {
            echo "<script>alert('❌ Error: " . addslashes($e->getMessage()) . "'); history.back();</script>";
        }
    } else {
        echo "<script>alert('⚠️ Please fill in all fields'); history.back();</script>";
    }
}
?>
