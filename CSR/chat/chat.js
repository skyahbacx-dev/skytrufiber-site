// ========================================
// SkyTruFiber CSR Chat System - chat.js
// ========================================

let currentClientID = null;
let messageInterval;
let clientRefreshInterval;

$(document).ready(function () {

    // First load clients
    loadClients();

    // Refresh client list every 4 seconds
    clientRefreshInterval = setInterval(loadClients, 4000);

    // Search filter
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    // Send message button
    $("#send-btn").click(function () {
        sendMessage();
    });

    // Send message via Enter
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Upload file button
    $("#upload-btn").click(function () {
        $("#chat-upload-media").click();
    });

    $("#chat-upload-media").change(function () {
        if (currentClientID) {
            uploadMedia();
        }
    });

    // Selecting a client
    $(document).on("click", ".client-item", function (e) {

        // ignore clicking icons
        if ($(e.target).closest(".client-icons").length) return;

        currentClientID = $(this).data("id");
        let clientName = $(this).data("name");

        $("#chat-client-name").text(clientName);
        $("#chat-messages").html("");

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);

        messageInterval = setInterval(function () {
            loadMessages(false);
        }, 1500);
    });

    // Assign client (+)
    $(document).on("click", ".add-client", function (e) {
        e.stopPropagation();
        let cid = $(this).data("id");
        assignClient(cid);
    });

    // Unassign client (–)
    $(document).on("click", ".remove-client", function (e) {
        e.stopPropagation();
        let cid = $(this).data("id");
        unassignClient(cid);
    });

    // Lock icon does nothing but prevents events
    $(document).on("click", ".lock-client", function (e) {
        e.stopPropagation();
    });

}); // end document ready

// ========================================
// LOAD CLIENT LIST
// ========================================
function loadClients() {
    $.ajax({
        url: "../chat/load_clients.php",
        type: "POST",
        success: function (html) {
            $("#client-list").html(html);
        }
    });
}

// ========================================
// LOAD CLIENT INFO (RIGHT PANEL)
// ========================================
function loadClientInfo(id) {
    $.ajax({
        url: "../chat/load_client_info.php",
        type: "POST",
        data: { client_id: id },
        success: function (html) {
            $("#client-info-content").html(html);
        }
    });
}

// ========================================
// LOAD MESSAGES
// ========================================
function loadMessages(scrollBottom) {
    if (!currentClientID) return;

    $.ajax({
        url: "../chat/load_messages.php",
        type: "POST",
        data: { client_id: currentClientID },
        success: function (html) {
            $("#chat-messages").html(html);

            if (scrollBottom) {
                $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
            }
        }
    });
}

// ========================================
// SEND TEXT MESSAGE
// ========================================
function sendMessage() {
    let msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID) return;

    $.ajax({
        url: "../chat/send_message.php",
        type: "POST",
        data: {
            client_id: currentClientID,
            message: msg,
            sender_type: "csr"
        },
        success: function () {
            $("#chat-input").val("");
            loadMessages(true);
        }
    });
}

// ========================================
// UPLOAD MEDIA
// ========================================
function uploadMedia() {
    const fileInput = $("#chat-upload-media")[0];
    if (!fileInput.files.length) return;

    const formData = new FormData();
    formData.append("media", fileInput.files[0]);
    formData.append("client_id", currentClientID);
    formData.append("csr", $("#csr-username").val());

    $.ajax({
        url: "../chat/media_upload.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function () {
            $("#chat-upload-media").val("");
            loadMessages(true);
        }
    });
}

// ========================================
// ASSIGN CLIENT (+)
// ========================================
function assignClient(cid) {
    $.post("../chat/assign_client.php", { client_id: cid }, function () {
        loadClients();
        if (cid == currentClientID) loadClientInfo(cid);
    });
}

// ========================================
// UNASSIGN CLIENT (–)
// ========================================
function unassignClient(cid) {
    $.post("../chat/unassign_client.php", { client_id: cid }, function () {
        loadClients();
        if (cid == currentClientID) {
            $("#client-info-content").html("<p>Select a client.</p>");
        }
    });
}
