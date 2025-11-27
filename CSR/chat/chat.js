// =======================
// GLOBAL VARIABLES
// =======================
let selectedClient = null;
let messagePolling = null;

// LOAD CLIENT LIST
function loadClients() {
    $.post("load_clients.php", function(data) {
        $("#client-list").html(data);
    });
}
loadClients();
setInterval(loadClients, 8000);   // auto-refresh

// SELECT CLIENT HANDLER
function selectClient(clientID) {
    selectedClient = clientID;

    // Load client info
    $.post("load_client_info.php", { id: clientID }, function(data) {
        $("#client-info-content").html(data);
    });

    // Load messages
    loadMessages();

    if (messagePolling) clearInterval(messagePolling);
    messagePolling = setInterval(loadMessages, 1800);
}

// LOAD MESSAGES
function loadMessages() {
    if (!selectedClient) return;

    $.post("load_messages.php", { client_id: selectedClient }, function(data) {
        $("#message-list").html(data);
        $("#message-list").scrollTop($("#message-list")[0].scrollHeight);
    });
}

// SEND MESSAGE (CSR SIDE)
function sendMessage() {
    let msg = $("#chat-input").val().trim();
    if (msg.length === 0 || !selectedClient) return;

    $.post("send_message.php", {
        client_id: selectedClient,
        message: msg,
        sender_type: "csr"
    }, function() {
        $("#chat-input").val("");
        loadMessages();
    });
}

// ENTER KEY SEND
$("#chat-input").keypress(function(e) {
    if (e.which === 13) sendMessage();
});

// ===============================
// ACTION BUTTON LEGEND
// + (assign)     | assign_client.php
// – (unassign)   | unassign_client.php
// 🔒 lock client | lock_client.php
// 🔓 unlock      | unlock_client.php
// ===============================

// ASSIGN CLIENT
function assignClient() {
    $.post("assign_client.php", { id: selectedClient }, function(res) {
        alert(res);
        loadClients();
        selectClient(selectedClient);
    });
}

// UNASSIGN CLIENT
function unassignClient() {
    $.post("unassign_client.php", { id: selectedClient }, function(res) {
        alert(res);
        loadClients();
        $("#client-info-content").html("<p>Select a Client</p>");
        selectedClient = null;
    });
}

// LOCK CLIENT
function lockClient() {
    $.post("lock_client.php", { id: selectedClient }, function(res) {
        alert(res);
        selectClient(selectedClient);
    });
}

// UNLOCK CLIENT
function unlockClient() {
    $.post("unlock_client.php", { id: selectedClient }, function(res) {
        alert(res);
        selectClient(selectedClient);
    });
}

// TYPING STATUS (OPTIONAL IF YOU HAVE ENDPOINT)
$("#chat-input").on("input", function() {
    if (!selectedClient) return;

    $.post("typing_update.php", {
        client_id: selectedClient,
        typing: true
    });
});

// UPLOAD MEDIA
function uploadMedia() {
    let file = $("#media-upload")[0].files[0];
    if (!file || !selectedClient) return;

    let formData = new FormData();
    formData.append("file", file);
    formData.append("client_id", selectedClient);

    $.ajax({
        url: "upload_media.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function() {
            loadMessages();
        }
    });
}

function triggerUpload() {
    $("#media-upload").click();
}
