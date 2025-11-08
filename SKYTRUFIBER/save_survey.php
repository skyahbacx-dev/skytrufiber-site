<?php
include '../db_connect.php'; // ensure path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form fields and sanitize
    $client_name  = trim($_POST['client_name'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');
    $feedback     = trim($_POST['feedback'] ?? '');
    $location     = trim($_POST['location'] ?? ''); // if you added location field

    if ($client_name && $account_name && $feedback) {
        try {
            // Prepare insert query using PDO
            $stmt = $conn->prepare("
                INSERT INTO survey_responses (client_name, account_name, feedback, location, created_at)
                VALUES (:client_name, :account_name, :feedback, :location, NOW())
            ");

            // Bind parameters safely
            $stmt->bindParam(':client_name', $client_name);
            $stmt->bindParam(':account_name', $account_name);
            $stmt->bindParam(':feedback', $feedback);
            $stmt->bindParam(':location', $location);

            // Execute the statement
            $stmt->execute();

            echo "
            <script>
              alert('✅ Thank you for your feedback, " . htmlspecialchars($client_name) . "!');
              window.location.href='skytrufiber.php';
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
          alert('⚠️ Please fill in all fields before submitting.');
          window.history.back();
        </script>";
    }
}
?>
