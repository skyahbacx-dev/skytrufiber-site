// ============================
// GLOBALS
// ============================
let selectedClientID = null;
let messageInterval = null;
let clientLocked = false;

// ============================
// LOAD ALL CLIENTS
// ============================
function loadClients() {
    $.ajax({
        url: "load_clients.php",
        type: "POST",
        success: function(data) {
            $("#client-list").html(data);
        }
    });
}
loadClients();
setInterval(loadClients, 4000);

// ============================
// SELECT CLIENT
// ============================
function selectClient(id) {
    selectedClientID = id;

    // load client info panel
    $.post("load_client_info.php", { id: id }, function(html) {
        $("#client-info-content").html(html);
    });

    // load lock status
    $.post("check_lock.php", { id: id }, function(status) {
        clientLocked = (status.trim() === "locked");
        updateLockUI();
    });

    // load chat messages
    loadMessages();
    if (messageInterval) clearInterval(messageInterval);
    messageInterval = setInterval(loadMessages, 1500);

    $("#chat-client-name").text("Loading...");
}

// ============================
// LOAD CHAT MESSAGES
// ============================
function loadMessages() {
    if (!selectedClientID) return;

    $.post("load_messages.php", { client_id: selectedClientID }, function(data) {
        $("#chat-messages").html(data);
        $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
    });
}

// ============================
// SEND MESSAGE
// ============================
$("#send-btn").click(() => sendMessage());
$("#chat-input").keypress(e => { if (e.which === 13) sendMessage(); });

function sendMessage() {
    if (clientLocked) return;
    const msg = $("#chat-input").val().trim();
    if (!msg || !selectedClientID) return;

    $.post("send_message.php", {
        client_id: selectedClientID,
        message: msg,
        sender_type: "csr"
    }, function() {
        $("#chat-input").val("");
        loadMessages();
    });
}

// ============================
// MEDIA UPLOAD
// ============================
$("#upload-btn").click(() => {
    if (!clientLocked) $("#chat-upload-media").click();
});

$("#chat-upload-media").change(function () {
    if (clientLocked) return;

    const file = this.files[0];
    if (!file || !selectedClientID) return;

    const f = new FormData();
    f.append("file", file);
    f.append("client_id", selectedClientID);

    $.ajax({
        url: "upload_media.php",
        type: "POST",
        data: f,
        contentType: false,
        processData: false,
        success: function() {
            loadMessages();
        }
    });
});

// ============================
// ACTION BUTTONS
// ============================
function assignClient() {
    $.post("assign_client.php", { id: selectedClientID }, res => alert(res));
}

function unassignClient() {
    $.post("unassign_client.php", { id: selectedClientID }, res => alert(res));
}

function lockClient() {
    $.post("lock_client.php", { id: selectedClientID }, res => {
        alert(res);
        clientLocked = true;
        updateLockUI();
    });
}

// ============================
// LOCK UI HANDLER
// ============================
function updateLockUI() {
    if (clientLocked) {
        $("#chat-input").prop("disabled", true);
        $("#send-btn").prop("disabled", true);
        $("#upload-btn").prop("disabled", true);
        $("#lock-overlay").show();
    } else {
        $("#chat-input").prop("disabled", false);
        $("#send-btn").prop("disabled", false);
        $("#upload-btn").prop("disabled", false);
        $("#lock-overlay").hide();
    }
}
