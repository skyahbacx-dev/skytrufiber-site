// ==============================
// CSR CHAT – FULL JS
// ==============================

let currentClientID = null;
let messagePoll = null;

$(document).ready(function () {

    // initial load + polling for clients
    loadClients();
    setInterval(loadClients, 4000);

    // search filter
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    // send message by button
    $("#send-btn").on("click", function () {
        sendMessage();
    });

    // send by Enter
    $("#chat-input").on("keypress", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        } else {
            // optional typing ping if you have typing_update.php
            if (currentClientID) {
                $.post("../chat/typing_update.php", {
                    client_id: currentClientID,
                    typing: 1,
                    user: "csr"
                });
            }
        }
    });

    // upload media
    $("#upload-btn").on("click", function () {
        $("#chat-upload-media").click();
    });

    $("#chat-upload-media").on("change", function () {
        if (currentClientID) {
            uploadMedia();
        }
    });

    // delegate client row click
    $(document).on("click", ".client-item", function (e) {
        // ignore click from action buttons (they handle themselves)
        if ($(e.target).closest(".client-row-actions").length) return;

        const id   = $(this).data("id");
        const name = $(this).data("name");

        currentClientID = id;
        $("#chat-client-name").text(name);

        loadClientInfo(id);
        loadMessages(true);

        if (messagePoll) clearInterval(messagePoll);
        messagePoll = setInterval(() => {
            if (currentClientID) loadMessages(false);
        }, 2000);
    });

    // delegate assign / remove / lock icons
    $(document).on("click", ".add-client", function (e) {
        e.stopPropagation();
        const id = $(this).data("id");
        assignClient(id);
    });

    $(document).on("click", ".remove-client", function (e) {
        e.stopPropagation();
        const id = $(this).data("id");
        removeClient(id);
    });

    // lock is just visual for now – no action, but prevent click bubbling
    $(document).on("click", ".lock-client", function (e) {
        e.stopPropagation();
    });

});

// ==============================
// CLIENT LIST
// ==============================
function loadClients() {
    $.ajax({
        url: "../chat/load_clients.php",
        type: "POST",
        success: function (html) {
            $("#client-list").html(html);
        }
    });
}

// ==============================
// CLIENT INFO (RIGHT PANEL)
// ==============================
function loadClientInfo(clientID) {
    $.ajax({
        url: "../chat/load_client_info.php",
        type: "POST",
        data: { client_id: clientID },
        success: function (html) {
            $("#client-info-content").html(html);
        }
    });
}

// ==============================
// MESSAGES
// ==============================
function loadMessages(scrollToBottom) {
    if (!currentClientID) return;

    $.ajax({
        url: "../chat/load_messages.php",
        type: "POST",
        data: { client_id: currentClientID },
        success: function (html) {
            $("#chat-messages").html(html);
            if (scrollToBottom) {
                $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
            }
        }
    });
}

function sendMessage() {
    const text = $("#chat-input").val().trim();
    if (!text || !currentClientID) return;

    $.ajax({
        url: "../chat/send_message.php",
        type: "POST",
        data: {
            client_id: currentClientID,
            message: text,
            sender_type: "csr"
        },
        success: function () {
            $("#chat-input").val("");
            loadMessages(true);
        }
    });
}

function uploadMedia() {
    const fileInput = $("#chat-upload-media")[0];
    if (!fileInput.files.length) return;

    const formData = new FormData();
    formData.append("media", fileInput.files[0]);
    formData.append("client_id", currentClientID);
    formData.append("user", "csr");

    $.ajax({
        url: "../chat/upload_media.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function () {
            $("#chat-upload-media").val("");
            loadMessages(true);
        }
    });
}

// ==============================
// ASSIGN / UNASSIGN
// ==============================
function assignClient(clientID) {
    $.post("../chat/assign_client.php", { client_id: clientID }, function () {
        loadClients();
    });
}

function removeClient(clientID) {
    $.post("../chat/unassign_client.php", { client_id: clientID }, function () {
        loadClients();
    });
}
