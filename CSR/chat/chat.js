// =========================
// CSR CHAT.JS - FULL FILE
// =========================

let currentClientID = null;
let refreshInterval = null;

$(document).ready(function () {

    // Load client list immediately
    loadClients();

    // Refresh client list every 4 seconds
    setInterval(loadClients, 4000);

    // Search clients live
    $("#client-search").on("keyup", function () {
        const searchVal = $(this).val().toLowerCase();
        $("#client-list .client-item").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(searchVal) > -1);
        });
    });

    // Send text message
    $("#send-btn").on("click", function () {
        sendMessage();
    });

    $("#chat-input").on("keypress", function (e) {
        if (e.which === 13) {
            sendMessage();
        }
    });

    // Upload file button
    $("#upload-btn").on("click", function () {
        $("#chat-upload-media").click();
    });

    $("#chat-upload-media").on("change", function () {
        uploadMedia();
    });

});


// =========================
// LOAD CLIENT LIST
// =========================
function loadClients() {
    $.ajax({
        url: "../chat/load_clients.php",
        method: "POST",
        success: function (response) {
            $("#client-list").html(response);

            // Rebind click event to each client
            $(".client-item").off().on("click", function () {
                const clientID = $(this).data("id");
                const clientName = $(this).data("name");

                currentClientID = clientID;
                $("#chat-client-name").text(clientName);

                loadClientInfo(clientID);
                loadMessages(clientID);

                if (refreshInterval) clearInterval(refreshInterval);
                refreshInterval = setInterval(() => loadMessages(clientID), 2000);
            });
        }
    });
}


// =========================
// LOAD CLIENT DETAILS
// =========================
function loadClientInfo(clientID) {
    $.ajax({
        url: "../chat/load_client_info.php",
        method: "POST",
        data: { client_id: clientID },
        success: function (html) {
            $("#client-info-content").html(html);
        }
    });
}


// =========================
// LOAD MESSAGES
// =========================
function loadMessages(clientID) {
    $.ajax({
        url: "../chat/load_messages.php",
        method: "POST",
        data: { client_id: clientID },
        success: function (html) {
            $("#chat-messages").html(html);
            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        }
    });
}


// =========================
// SEND TEXT MESSAGE
// =========================
function sendMessage() {
    const msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID) return;

    $.ajax({
        url: "../chat/send_message.php",
        method: "POST",
        data: {
            client_id: currentClientID,
            message: msg,
            sender: "csr"
        },
        success: function () {
            $("#chat-input").val("");
            loadMessages(currentClientID);
        }
    });
}


// =========================
// UPLOAD MEDIA MESSAGE
// =========================
function uploadMedia() {
    if (!currentClientID) return;

    let fileData = $("#chat-upload-media")[0].files[0];
    let formData = new FormData();
    formData.append("media", fileData);
    formData.append("client_id", currentClientID);

    $.ajax({
        url: "../chat/upload_media.php",
        method: "POST",
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        success: function () {
            loadMessages(currentClientID);
        }
    });
}


// =========================
// ASSIGN CLIENT
// =========================
function assignClient(clientID) {
    $.ajax({
        url: "../chat/assign_client.php",
        method: "POST",
        data: { client_id: clientID },
        success: function () {
            loadClients();
        }
    });
}


// =========================
// REMOVE CLIENT
// =========================
function removeClient(clientID) {
    $.ajax({
        url: "../chat/unassign_client.php",
        method: "POST",
        data: { client_id: clientID },
        success: function () {
            loadClients();
        }
    });
}


// =========================
// LOCK CLIENT
// =========================
function lockClient(clientID) {
    $.ajax({
        url: "../chat/block_client.php",
        method: "POST",
        data: { client_id: clientID },
        success: function () {
            loadClients();
        }
    });
}
