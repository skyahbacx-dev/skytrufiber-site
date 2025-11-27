// =============================================
// SKYTRUFIBER CSR CHAT — FULL chat.js
// =============================================

// GLOBAL STATE
let selectedClientID = null;
let selectedClientName = null;
let refreshClientsInterval = null;
let refreshMessagesInterval = null;

// Load clients list
function loadClients() {
    $.ajax({
        url: "load_clients.php",
        method: "POST",
        success: function(res) {
            $("#client-list").html(res);
        }
    });
}

// Auto refresh every 5 seconds
function startClientAutoRefresh() {
    if (refreshClientsInterval) clearInterval(refreshClientsInterval);
    refreshClientsInterval = setInterval(loadClients, 5000);
}

// Select a client from list
function selectClient(clientID, clientName) {
    selectedClientID = clientID;
    selectedClientName = clientName;
    $("#chat-client-name").text(clientName);
    $("#chat-messages").html("");

    loadMessages();
    loadClientInfo();
    startMessageAutoRefresh();
}

// Load chat messages for selected client
function loadMessages() {
    if (!selectedClientID) return;

    $.ajax({
        url: "load_messages.php",
        method: "POST",
        data: { client_id: selectedClientID },
        success: function(res) {
            $("#chat-messages").html(res);
            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        }
    });
}

// Auto refresh messages
function startMessageAutoRefresh() {
    if (refreshMessagesInterval) clearInterval(refreshMessagesInterval);
    refreshMessagesInterval = setInterval(loadMessages, 2000);
}

// Send message
$("#send-btn").on("click", function () {
    sendMessage();
});

$("#chat-input").keypress(function (e) {
    if (e.which === 13) sendMessage();
});

function sendMessage() {
    if (!selectedClientID) return alert("Select a client first.");
    let msg = $("#chat-input").val().trim();
    if (msg === "") return;

    $.ajax({
        url: "send_message.php",
        method: "POST",
        data: { client_id: selectedClientID, message: msg },
        success: function (response) {
            if (response === "LOCKED") {
                alert("Client is locked and cannot receive messages.");
                return;
            }
            $("#chat-input").val("");
            loadMessages();
        }
    });
}

// Load client details panel
function loadClientInfo() {
    $.ajax({
        url: "load_client_info.php",
        method: "POST",
        data: { client_id: selectedClientID },
        success: function(res) {
            $("#client-info-content").html(res);
        }
    });
}

// Assign CSR (+)
function assignClient(clientID) {
    $.ajax({
        url: "assign_client.php",
        method: "POST",
        data: { client_id: clientID },
        success: function() {
            loadClients();
            loadClientInfo();
        }
    });
}

// Unassign CSR (–)
function unassignClient(clientID) {
    $.ajax({
        url: "unassign_client.php",
        method: "POST",
        data: { client_id: clientID },
        success: function() {
            loadClients();
            loadClientInfo();
        }
    });
}

// Lock user (🔒)
function lockClient(clientID) {
    $.ajax({
        url: "block_client.php",
        method: "POST",
        data: { client_id: clientID },
        success: function() {
            loadClients();
            loadClientInfo();
        }
    });
}

// Upload button
$("#upload-btn").on("click", function () {
    $("#chat-upload-media").click();
});

// Media upload
$("#chat-upload-media").on("change", function () {
    let fileData = new FormData();
    fileData.append("client_id", selectedClientID);
    fileData.append("media", $("#chat-upload-media")[0].files[0]);

    $.ajax({
        url: "upload_media.php",
        method: "POST",
        data: fileData,
        cache: false,
        processData: false,
        contentType: false,
        success: function(response) {
            loadMessages();
        }
    });
});

// Start system
$(document).ready(function () {
    loadClients();
    startClientAutoRefresh();
});
