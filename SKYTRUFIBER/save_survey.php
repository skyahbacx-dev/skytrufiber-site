<?php
include '../db_connect.php'; // make sure this path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form fields and sanitize
    $client_name  = trim($_POST['client_name'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');
    $feedback     = trim($_POST['feedback'] ?? '');

    if ($client_name && $account_name && $feedback) {
        // Prepare insert query
        $stmt = $conn->prepare("
            INSERT INTO survey_responses (client_name, account_name, feedback, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("sss", $client_name, $account_name, $feedback);

        if ($stmt->execute()) {
            echo "
            <script>
              alert('✅ Thank you for your feedback, " . htmlspecialchars($client_name) . "!');
              window.location.href='skytrufiber.php';
            </script>";
        } else {
            echo "
            <script>
              alert('❌ Something went wrong while saving your feedback. Please try again.');
              window.history.back();
            </script>";
        }
        $stmt->close();
    } else {
        echo "
        <script>
          alert('⚠️ Please fill in all fields before submitting.');
          window.history.back();
        </script>";
    }
}
?>
