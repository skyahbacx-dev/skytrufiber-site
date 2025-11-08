<?php
include '../db_connect.php'; // ensure path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🧹 Sanitize and collect form data
    $client_name  = trim($_POST['client_name'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');
    $district     = trim($_POST['district'] ?? '');
    $location     = trim($_POST['location'] ?? ''); // Barangay
    $feedback     = trim($_POST['feedback'] ?? '');

    // ✅ Check for missing fields
    if ($client_name && $account_name && $district && $location && $feedback) {
        try {
            // ✅ Insert into your NeonDB table
            $stmt = $conn->prepare("
                INSERT INTO survey_responses (client_name, account_name, district, location, feedback, created_at)
                VALUES (:client_name, :account_name, :district, :location, :feedback, NOW())
            ");

            $stmt->bindParam(':client_name', $client_name);
            $stmt->bindParam(':account_name', $account_name);
            $stmt->bindParam(':district', $district);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':feedback', $feedback);

            $stmt->execute();

            echo "
            <script>
              alert('✅ Thank you for your feedback, " . htmlspecialchars($client_name) . "! Your response has been recorded.');
              window.location.href='register.php';
            </script>";
        } catch (PDOException $e) {
            echo "
            <script>
              alert('❌ Error saving feedback: " . addslashes($e->getMessage()) . "');
              window.history.back();
            </script>";
        }
    } else {
        echo "
        <script>
          alert('⚠️ Please complete all fields before submitting.');
          window.history.back();
        </script>";
    }
}
?>
