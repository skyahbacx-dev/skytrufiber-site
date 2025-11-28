// ========================================
// SkyTruFiber CSR Chat System - chat.js (Optimized)
// ========================================

let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let selectedFile = null;
let lastMessageID = 0;

$(document).ready(function () {

    loadClients();

    clientRefreshInterval = setInterval(loadClients, 4000);

    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    $("#upload-btn").click(() => $("#chat-upload-media").click());

    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;
        previewFile(this.files[0]);
    });

    $(document).on("click", ".client-item", function (e) {
        if ($(e.target).closest(".client-icons").length) return;

        currentClientID = $(this).data("id");
        const name = $(this).data("name");

        $("#chat-client-name").text(name);
        $("#chat-messages").html("");
        lastMessageID = 0;

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(() => {
            if (!$("#preview-overlay").is(":visible")) {
                loadMessages(false);
            }
        }, 2000);
    });

    $(document).on("click", ".add-client", function (e) {
        e.stopPropagation();
        assignClient($(this).data("id"));
    });

    $(document).on("click", ".remove-client", function (e) {
        e.stopPropagation();
        unassignClient($(this).data("id"));
    });

    $(document).on("click", ".media-thumb", function () {
        $("#lightbox-image").attr("src", $(this).attr("src"));
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").click(function () {
        $("#lightbox-overlay").fadeOut(200);
    });

    $("#cancel-preview").click(function () {
        selectedFile = null;
        $("#preview-overlay").fadeOut(200);
    });

    $("#send-preview").click(function () {
        if (selectedFile) uploadMedia(selectedFile);
    });

});


// ========================================
// LOAD CLIENT LIST
// ========================================
function loadClients() {
    $.post("../chat/load_clients.php", function (html) {
        $("#client-list").html(html);
    });
}


// ========================================
// LOAD CLIENT INFO
// ========================================
function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, function (html) {
        $("#client-info-content").html(html);
    });
}


// ========================================
// LOAD MESSAGES (without flicker)
// ========================================
function loadMessages(scrollBottom = false) {
    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (res) {

        const parsed = $(res);
        const lastBubble = parsed.last();
        const newLastID = parseInt(lastBubble.attr("data-msg-id"));

        if (newLastID > lastMessageID) {
            lastMessageID = newLastID;
            $("#chat-messages").append(parsed);

            const box = $("#chat-messages");
            if (scrollBottom) box.scrollTop(box[0].scrollHeight);
        }
    });
}


// ========================================
// SEND TEXT
// ========================================
function sendMessage() {
    let msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID) return;

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg,
        sender_type: "csr"
    }, function (res) {
        if (res.status === "ok") {
            $("#chat-input").val("");
            loadMessages(true);
        } else {
            alert("Send failed");
        }
    }, "json");
}


// ========================================
// PREVIEW FILE
// ========================================
function previewFile(file) {
    selectedFile = file;

    if (file.type.startsWith("image")) {
        const reader = new FileReader();
        reader.onload = e => {
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
    const fd = new FormData();
    fd.append("media", file);
    fd.append("client_id", currentClientID);
    fd.append("sender_type", "csr");

    $("#preview-overlay").fadeOut(200);

    $.ajax({
        url: "../chat/media_upload.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        dataType: "json",

        success: function (res) {
            selectedFile = null;
            $("#chat-upload-media").val("");

            if (res.status === "ok") loadMessages(true);
            else alert("Upload failed");
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
