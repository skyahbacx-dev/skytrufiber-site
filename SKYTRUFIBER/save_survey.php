<?php
include '../db_connect.php'; // ensure path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🧹 Sanitize and collect form data
    $client_name     = trim($_POST['client_name'] ?? '');
    $account_name    = trim($_POST['account_name'] ?? '');
    $email           = trim($_POST['email'] ?? ''); // NEW ✅
    $district        = trim($_POST['district'] ?? '');
    $location        = trim($_POST['location'] ?? ''); // Barangay
    $date_installed  = trim($_POST['date_installed'] ?? ''); // NEW ✅
    $feedback        = trim($_POST['feedback'] ?? '');

    // ✅ Check for missing required fields
    if (!$client_name || !$account_name || !$district || !$location || !$feedback) {
        echo "
        <script>
          alert('⚠️ Please fill in all required fields before submitting.');
          window.history.back();
        </script>";
        exit;
    }

    try {
        // ✅ Insert into survey_responses table
        $stmt = $conn->prepare("
            INSERT INTO survey_responses (
                client_name,
                account_name,
                email,
                district,
                location,
                date_installed,
                feedback,
                created_at
            ) VALUES (
                :client_name,
                :account_name,
                :email,
                :district,
                :location,
                :date_installed,
                :feedback,
                NOW()
            )
        ");

        $stmt->execute([
            ':client_name'    => $client_name,
            ':account_name'   => $account_name,
            ':email'          => $email ?: null, // allow NULL
            ':district'       => $district,
            ':location'       => $location,
            ':date_installed' => $date_installed ?: null, // allow NULL
            ':feedback'       => $feedback
        ]);

        echo "
        <script>
          alert('✅ Thank you for your feedback, " . htmlspecialchars($client_name) . "! Your response has been recorded.');
          window.location.href='register.php';
        </script>";

    } catch (PDOException $e) {

        $msg = addslashes($e->getMessage());

        echo "
        <script>
          alert('❌ Database error: $msg');
          window.history.back();
        </script>";
    }
}
?>
