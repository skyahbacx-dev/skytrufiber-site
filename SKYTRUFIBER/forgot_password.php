<?php
header("Content-Type: application/json");
include "../db_connect.php";

$email = trim($_POST["email"] ?? "");

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Email is required."]);
    exit;
}

try {
    // Find user
    $stmt = $conn->prepare("SELECT id, full_name, email, account_number FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([":email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "No account found with that email."]);
        exit;
    }

    // Prepare email content
    $to      = $user["email"];
    $subject = "SkyTruFiber - Your Account Number (Password Recovery)";
    $message = "
Hello {$user['full_name']},

You requested to retrieve your password.

Your Account Number is: **{$user['account_number']}**

You may now use this as your password to log in.

Best regards,
SkyTruFiber Support
";

    $headers = "From: no-reply@skytrufiber.com\r\n";
    $headers .= "Reply-To: support@skytrufiber.com\r\n";

    // Send mail
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(["status" => "success", "message" => "Your account number has been sent to your email."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send email. Please try again later."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "DB Error: " . $e->getMessage()]);
}
?>
