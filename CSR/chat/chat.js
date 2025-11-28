// ========================================
// SkyTruFiber CSR Chat System - chat.js
// ========================================

let currentClientID = null;
let messageInterval;
let clientRefreshInterval;
let selectedFile = null;

$(document).ready(function () {

    loadClients(); // first load clients

    // auto-refresh client list
    clientRefreshInterval = setInterval(loadClients, 4000);

    // search filter
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    // send message button
    $("#send-btn").click(sendMessage);

    // enter to send
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // upload button
    $("#upload-btn").click(() => $("#chat-upload-media").click());

    // file selected
    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;
        previewFile(this.files[0]);
    });

    // selecting a client
    $(document).on("click", ".client-item", function (e) {
        if ($(e.target).closest(".client-icons").length) return; // ignore icons

        currentClientID = $(this).data("id");
        let name = $(this).data("name");

        $("#chat-client-name").text(name);
        $("#chat-messages").html("");

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(() => loadMessages(false), 1500);
    });

    // assign / unassign clients
    $(document).on("click", ".add-client", function (e) {
        e.stopPropagation();
        assignClient($(this).data("id"));
    });

    $(document).on("click", ".remove-client", function (e) {
        e.stopPropagation();
        unassignClient($(this).data("id"));
    });

    $(document).on("click", ".lock-client", function (e) {
        e.stopPropagation();
    });

    // LIGHTBOX image opening
    $(document).on("click", ".media-thumb", function () {
        const src = $(this).attr("src");
        $("#lightbox-image").attr("src", src);
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").click(function () {
        $("#lightbox-overlay").fadeOut(200);
    });

    // Preview action buttons
    $("#cancel-preview").click(function () {
        selectedFile = null;
        $("#preview-overlay").fadeOut(200);
    });

    $("#send-preview").click(function () {
        if (selectedFile) {
            uploadMedia(selectedFile);
        }
    });

}); // end document ready


// ========================================
// LOAD CLIENTS
// ========================================
function loadClients() {
    $.post("../chat/load_clients.php", function (html) {
        $("#client-list").html(html);
    });
}

// ========================================
// LOAD CLIENT DETAILS (right panel)
// ========================================
function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, function (html) {
        $("#client-info-content").html(html);
    });
}

// ========================================
// LOAD MESSAGES
// ========================================
// Instead of full html() replace, use something like:
function loadMessages() {
  if (!currentClientID) return;

  $.post("../chat/load_messages.php", { client_id: currentClientID }, function(html) {
    const $container = $("#chat-messages");
    // Option A: simple — only replace if different
    if ($container.html() !== html) {
      $container.html(html);
      $container.scrollTop($container[0].scrollHeight);
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
        dataType: "json",

        success: function (res) {
            if (res.status === "ok") {
                $("#chat-input").val("");
                loadMessages(true);
            } else {
                alert("Send failed: " + res.msg);
            }
        },
        error: function () {
            alert("Network or server error");
        }
    });
}


// ========================================
// PREVIEW FILE BEFORE SENDING
// ========================================
function previewFile(file) {
    selectedFile = file;

    if (file.type.startsWith("image")) {
        const reader = new FileReader();
        reader.onload = function (e) {
            $("#preview-image").attr("src", e.target.result);
            $("#preview-overlay").fadeIn(200);
        };
        reader.readAsDataURL(file);
    } else {
        $("#preview-image").attr("src", "/CSR/chat/file-icon.png");
        $("#preview-overlay").fadeIn(200);
    }
}


// ========================================
// UPLOAD MEDIA
// ========================================
function uploadMedia(file) {
    const formData = new FormData();
    formData.append("media", file);
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
            $("#preview-overlay").hide();
            $("#chat-upload-media").val("");
            selectedFile = null;

            if (res.status === "ok") {
                loadMessages(true);
            } else {
                alert("Upload failed: " + res.msg);
            }
        },

        error: function (xhr) {
            alert("Upload error — check console");
            console.error(xhr.responseText);
        }
    });
}


// ========================================
// ASSIGN / UNASSIGN CLIENT
// ========================================
function assignClient(id) {
    $.post("../chat/assign_client.php", { client_id: id }, function () {
        loadClients();
        if (id == currentClientID) loadClientInfo(id);
    });
}

function unassignClient(id) {
    $.post("../chat/unassign_client.php", { client_id: id }, function () {
        loadClients();
        if (id == currentClientID) $("#client-info-content").html("<p>Select a client.</p>");
    });
}
