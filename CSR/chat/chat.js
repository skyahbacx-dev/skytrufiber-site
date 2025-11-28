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
// LOAD MESSAGES (MESSENGER BUBBLES)
// ========================================
function loadMessages(scrollBottom) {
    if (!currentClientID) return;

    $.ajax({
        url: "../chat/load_messages.php",
        type: "POST",
        data: { client_id: currentClientID },
        dataType: "html",
        success: function (html) {
            const chatBox = $("#chat-messages");
            chatBox.html(html);

            if (scrollBottom) {
                chatBox.scrollTop(chatBox[0].scrollHeight);
            }
        },
        error: function (err) {
            console.error("Message load error:", err);
        }
    });
}

// ========================================
// SEND TEXT MESSAGE (JSON RESPONSE)
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
    dataType: "json",
    xhrFields: { withCredentials: true },      // <-- REQUIRED FIX
    success: function (res) {
        $("#chat-input").val("");

        if (res.status === "ok") {
            loadMessages(true);
        } else {
            alert("Unable to send message.");
        }
    },
    error: function (err) {
        console.error(err);
        alert("Send error.");
    }
});

// ========================================
// UPLOAD MEDIA (AJAX FormData)
// ========================================
function uploadMedia() {
    const fileInput = $("#chat-upload-media")[0];
    if (!fileInput.files.length || !currentClientID) return;

    const formData = new FormData();
    formData.append("media", fileInput.files[0]);
    formData.append("client_id", currentClientID);
    formData.append("sender_type", "csr");

    $.ajax({
        url: "../chat/media_upload.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (res) {
            $("#chat-upload-media").val("");

            if (res.status === "ok") {
                loadMessages(true);
            } else {
                console.error("Upload failed:", res);
                alert("Media upload failed.");
            }
        },
        error: function (err) {
            console.error("Media upload error:", err);
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

// Lightbox Preview
$(document).on("click", ".media-thumb", function () {
    const src = $(this).attr("src");
    $("#lightbox-image").attr("src", src);
    $("#lightbox-overlay").fadeIn(200);
});

$("#lightbox-close, #lightbox-overlay").on("click", function () {
    $("#lightbox-overlay").fadeOut(200);
});
