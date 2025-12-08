<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}
$csrUser = $_SESSION["csr_user"];
?>

<div id="chat-container">

    <!-- LEFT PANEL -->
    <div class="chat-left-panel">

        <div class="left-header">
            <h3>Clients</h3>

            <!-- Ticket Filters -->
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
    <div class="chat-middle-panel">
        <div class="chat-wrapper">

            <!-- CHAT HEADER -->
            <div class="chat-header">
                <div class="chat-with">
                    <h3 id="chat-client-name">Select a Client</h3>
                    <span id="client-status" class="status-dot offline"></span>
                </div>

                <!-- Ticket Status Selector -->
                <div class="ticket-status-control">
                    <select id="ticket-status-dropdown" disabled>
                        <option value="unresolved">Unresolved</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
            </div>

            <!-- CHAT MESSAGES -->
            <div id="chat-messages" class="chat-messages"></div>

            <!-- SCROLL TO BOTTOM BUTTON -->
            <button id="scroll-bottom-btn" class="scroll-bottom-btn">
                <i class="fa fa-arrow-down"></i>
            </button>

            <!-- QUICK SUGGESTIONS -->
            <div class="quick-suggestions">
                <button class="qs-btn">No internet</button>
                <button class="qs-btn">Slow connection</button>
                <button class="qs-btn">Router blinking red</button>
                <button class="qs-btn">Please try restarting the router</button>
                <button class="qs-btn">Network checking…</button>
            </div>

            <!-- CHAT INPUT -->
            <div id="chat-input-wrapper" class="chat-input-area">
                <div class="chat-input-box">
                    <input type="text" id="chat-input" placeholder="Type a message..." autocomplete="off">
                </div>

                <button id="send-btn" class="chat-send-btn">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>

        </div> <!-- /chat-wrapper -->

        <!-- ACTION MENU POPUP -->
        <div id="msg-action-popup" class="msg-action-popup">
            <button class="action-edit"><i class="fa fa-pen"></i> Edit</button>
            <button class="action-unsend"><i class="fa fa-ban"></i> Unsend</button>
            <button class="action-delete"><i class="fa fa-trash"></i> Delete</button>
            <button class="action-cancel">Cancel</button>
        </div>

    </div> <!-- /chat-middle-panel -->

    <!-- RIGHT PANEL -->
    <div class="chat-right-panel">
        <div class="client-info-panel">
            <h3>Client Details</h3>
            <p>Select a client to view details</p>
            <div id="client-info-content"></div>
        </div>
    </div>

</div> <!-- /chat-container -->

<!-- Hidden CSR username -->
<input type="hidden" id="csr-username" value="<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>">

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
