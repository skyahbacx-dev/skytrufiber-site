<?php if (!isset($_SESSION)) session_start(); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkyTruFiber Support</title>

<!-- Prevent caching -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- CSS -->
<link rel="stylesheet" href="chat_support.css?v=<?php echo time(); ?>">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- JS -->
<script src="chat_support.js?v=<?php echo time(); ?>" defer></script>

</head>

<body>

<div id="chat-overlay">
    <div class="chat-modal">

        <!-- ======================================================
             HEADER AREA
        ======================================================= -->
        <div class="chat-header">

            <div class="chat-header-left">
                <img src="../../SKYTRUFIBER.png" class="chat-header-logo">
                <div>
                    <h2>SkyTruFiber Support</h2>

                    <span class="status active">Support Team Active</span>

                    <!-- Live Ticket Status Label -->
                    <span id="ticket-status-label" class="ticket-label" style="
                        display:block;
                        margin-top:4px;
                        font-size:13px;
                        color:#fff;
                        opacity:.9;
                    ">Checking ticket...</span>

                </div>
            </div>

            <div class="chat-header-right">

                <!-- THEME TOGGLE -->
                <button id="theme-toggle" class="theme-toggle">
                    <i class="fa-solid fa-moon theme-icon moon-icon"></i>
                    <i class="fa-solid fa-sun theme-icon sun-icon"></i>
                </button>

                <!-- LOGOUT -->
                <button id="logout-btn" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>

            </div>

        </div>

        <!-- ======================================================
             CHAT MESSAGES
        ======================================================= -->
        <div id="chat-messages" class="chat-messages"></div>

        <button id="scroll-bottom-btn" class="scroll-bottom-btn">
            <i class="fa-solid fa-arrow-down"></i>
        </button>

        <!-- ======================================================
             INPUT AREA (MEDIA & REACTIONS REMOVED)
        ======================================================= -->
        <div class="chat-input-area">

            <!-- CLIENT TEXT INPUT -->
            <div class="chat-input-box">
                <input id="message-input" type="text" placeholder="Type a message..." autocomplete="off">
            </div>

            <!-- SEND BUTTON -->
            <button id="send-btn" class="chat-send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>

        </div>

        <!-- QUICK SUGGESTIONS BAR
             (Injected by JS above input bar)
        -->

        <!-- ======================================================
             MESSAGE ACTION POPUP
        ======================================================= -->
        <div id="msg-action-popup" class="msg-action-popup">
            <button class="action-edit"><i class="fa-solid fa-pen"></i> Edit</button>
            <button class="action-unsend"><i class="fa-solid fa-ban"></i> Unsend</button>
            <button class="action-delete"><i class="fa-solid fa-trash"></i> Delete</button>
            <button class="action-cancel">Cancel</button>
        </div>

    </div> <!-- END CHAT MODAL -->
</div> <!-- END CHAT OVERLAY -->

</body>
</html>
