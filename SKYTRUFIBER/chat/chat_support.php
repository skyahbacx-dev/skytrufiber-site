<?php
if (!isset($_SESSION)) session_start();
include '../db_connect.php';

// Redirect if no session or ticket
if (!isset($_SESSION['client_id'], $_SESSION['ticket_id'])) {
    header("Location: ../skytrufiber.php");
    exit;
}

$clientId = $_SESSION['client_id'];
$ticketId = $_SESSION['ticket_id'];

// Fetch ticket status from tickets table
try {
    $stmt = $conn->prepare("SELECT status FROM tickets WHERE id = :tid AND client_id = :cid LIMIT 1");
    $stmt->execute([
        ':tid' => $ticketId,
        ':cid' => $clientId
    ]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("⚠ Ticket not found.");
    }

    $ticketStatus = $ticket['status'];

} catch (PDOException $e) {
    die("⚠ Database error: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>

<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkyTruFiber Support</title>

<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="chat_support.css?v=<?php echo time(); ?>">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="chat_support.js?v=<?php echo time(); ?>" defer></script>

</head>

<body>

<div id="chat-overlay">
    <div class="chat-modal">

```
    <!-- HEADER -->
    <div class="chat-header">

        <div class="chat-header-left">
            <img src="../../SKYTRUFIBER.png" class="chat-header-logo">
            <div>
                <h2>SkyTruFiber Support</h2>
                <span class="status active">Support Team Active</span>

                <!-- Live Ticket Status Label -->
                <span id="ticket-status-label" 
                      class="ticket-label"
                      style="display:block;margin-top:4px;font-size:13px;color:#fff;opacity:.9;">
                      Status: <?php echo htmlspecialchars(strtoupper($ticketStatus)); ?>
                </span>
            </div>
        </div>

        <div class="chat-header-right">
            <button id="theme-toggle" class="theme-toggle">
                <i class="fa-solid fa-moon theme-icon moon-icon"></i>
                <i class="fa-solid fa-sun theme-icon sun-icon"></i>
            </button>
            <button id="logout-btn" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </button>
        </div>

    </div>

    <!-- CHAT MESSAGES AREA -->
    <div id="chat-messages" class="chat-messages"></div>

    <!-- Scroll to Bottom -->
    <button id="scroll-bottom-btn" class="scroll-bottom-btn">
        <i class="fa-solid fa-arrow-down"></i>
    </button>

    <!-- FLOATING QUICK REPLIES -->
    <div id="quick-replies" class="quick-replies hidden">
        <button class="qr-btn">I am experiencing no internet.</button>
        <button class="qr-btn">My connection is slow.</button>
        <button class="qr-btn">My router is blinking red.</button>
        <button class="qr-btn">I already restarted my router.</button>
        <button class="qr-btn">Please assist me. Thank you.</button>
    </div>

    <!-- INPUT BAR -->
    <div class="chat-input-area">
        <div class="chat-input-box">
            <input id="message-input" type="text" placeholder="Type a message..." autocomplete="off">
        </div>
        <button id="send-btn" class="chat-send-btn">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>

    <!-- ACTION POPUP -->
    <div id="msg-action-popup" class="msg-action-popup">
        <button class="action-edit"><i class="fa-solid fa-pen"></i> Edit</button>
        <button class="action-unsend"><i class="fa-solid fa-ban"></i> Unsend</button>
        <button class="action-delete"><i class="fa-solid fa-trash"></i> Delete</button>
        <button class="action-cancel">Cancel</button>
    </div>

</div>
```

</div>

</body>
</html>
