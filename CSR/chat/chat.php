<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}
$csrUser = $_SESSION["csr_user"];
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>CSR Chat Panel</title>

<!-- ⭐ FIXED: FontAwesome CDN replaced for Safari/iPhone compatibility -->
<link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
      integrity="sha512-VzB+S2sPJHhZkVhcaC67DHDixstb7hpkc6E2r4e+VxfJYxHoxB6QBrf3KHAXzB5V+7I+6Yj/HWIR0a5hEzlzBw==" 
      crossorigin="anonymous" referrerpolicy="no-referrer">

<!-- CHAT CSS (correct folder path) -->
<link rel="stylesheet" href="chat.css?v=<?= time(); ?>">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
/* ---------------- GLOBAL FIXES ---------------- */

html, body {
    height: 100%;
    margin: 0;
    overflow: hidden;
}

#chat-container {
    display: flex;
    width: 100%;
    height: 100vh;
    overflow: hidden;
}

/* 🛠 Fix: Wrapper ensures scroll area + input bar never overlap */
.chat-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

/* ---------------- CHAT INPUT AREA ---------------- */
#chat-input-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    border-top: 1px solid #dcdcdc;
    background: var(--input-bg, #fff);
    z-index: 10;
    position: relative;
}

.chat-input-box {
    flex: 1;
}

#chat-input {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #ccc;
    font-size: 15px;
}

/* SEND BUTTON */
.chat-send-btn {
    background: #00a246;
    border: none;
    color: white;
    padding: 12px 16px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
}
.chat-send-btn:hover {
    background: #008639;
}

/* ---------------- MOBILE FIXES ---------------- */
@media (max-width: 600px) {

    #chat-input-wrapper {
        padding-bottom: calc(16px + env(safe-area-inset-bottom));
    }

    #chat-input {
        font-size: 17px;
        padding: 14px;
    }

    .chat-send-btn {
        padding: 14px 16px;
        font-size: 18px;
    }

    #scroll-bottom-btn {
        bottom: 92px !important;
    }
}
</style>

</head>

<body>

<div id="chat-container">

    <!-- LEFT PANEL -->
    <div class="chat-left-panel">

        <div class="left-header">
            <h3>Clients</h3>

            <div class="ticket-filter-buttons">
                <button class="ticket-filter" data-filter="all">All</button>
                <button class="ticket-filter" data-filter="unresolved">Unresolved</button>
                <button class="ticket-filter" data-filter="resolved">Resolved</button>
            </div>

            <input type="text" id="client-search" placeholder="Search clients...">
        </div>

        <div id="client-list" class="client-list"></div>
    </div>

    <!-- MIDDLE CHAT PANEL -->
    <div class="chat-middle-panel" id="ticket-border-panel">
        
        <div class="chat-wrapper">

            <!-- CHAT HEADER -->
            <div class="chat-header">
                <div class="chat-with">
                    <h3 id="chat-client-name">Select a Client</h3>
                    <span id="client-status" class="status-dot offline"></span>
                </div>

                <div class="ticket-status-control">
                    <select id="ticket-status-dropdown" disabled>
                        <option value="unresolved">Unresolved</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
            </div>

            <!-- CHAT MESSAGES -->
            <div id="chat-messages" class="chat-messages"></div>

            <!-- SCROLL TO BOTTOM BUTTON -->
            <button id="scroll-bottom-btn" class="scroll-bottom-btn">
                <i class="fa-solid fa-arrow-down"></i>
            </button>

            <!-- CHAT INPUT AREA -->
            <div id="chat-input-wrapper">
                <div class="chat-input-box">
                    <input type="text" id="chat-input" placeholder="Type a message..." autocomplete="off">
                </div>

                <button id="send-btn" class="chat-send-btn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>

        </div>

        <!-- ACTION POPUP -->
        <div id="msg-action-popup" class="msg-action-popup">
            <button class="action-edit"><i class="fa-solid fa-pen"></i> Edit</button>
            <button class="action-unsend"><i class="fa-solid fa-ban"></i> Unsend</button>
            <button class="action-delete"><i class="fa-solid fa-trash"></i> Delete</button>
            <button class="action-cancel">Cancel</button>
        </div>

    </div>

    <!-- RIGHT PANEL -->
    <div class="chat-right-panel">
        <div class="client-info-panel">
            <h3>Client Details</h3>
            <p>Select a client to view details</p>
            <div id="client-info-content"></div>
        </div>
    </div>

</div>

<!-- Hidden CSR username -->
<input type="hidden" id="csr-username" value="<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>">

<!-- SORTABLE AND CHAT SCRIPT -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<!-- ⭐ Correct path to chat.js -->
<script src="chat.js?v=<?= time(); ?>"></script>

</body>
</html>
