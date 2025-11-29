// ========================================
// SkyTruFiber CSR Chat System - chat.js
// Optimized + Multi File Upload + Reply Bar + Carousel Support
// ========================================

let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let selectedFiles = [];
let lastMessageID = 0;

$(document).ready(function () {

    loadClients();

    // Auto-refresh client list
    clientRefreshInterval = setInterval(loadClients, 4000);

    // Search
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    // Send message button
    $("#send-btn").click(sendMessage);

    // Press enter to send
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Upload button open file picker
    $("#upload-btn").click(() => $("#chat-upload-media").click());

    // Multiple file selection
    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;

        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // Selecting a client
    $(document).on("click", ".client-item", function () {
        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));
        $("#chat-messages").html("");
        lastMessageID = 0;

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(() => {
            if (!$("#preview-overlay").is(":visible")) loadMessages(false);
        }, 1500);
    });

    // Lightbox image viewer
    $(document).on("click", ".media-thumb", function () {
        $("#lightbox-image").attr("src", $(this).attr("src"));
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").click(() => $("#lightbox-overlay").fadeOut(200));

    // Cancel multiple file preview
    $("#cancel-preview").click(function () {
        selectedFiles = [];
        $("#preview-files").html("");
        $("#preview-overlay").fadeOut(200);
    });

    // Send selected previewed files
    $("#send-preview").click(() => {
        if (selectedFiles.length > 0) uploadMedia(selectedFiles);
    });

}); // END DOCUMENT READY


// ========================================
// LOAD CLIENT LIST
// ========================================
function loadClients() {
    $.post("../chat/load_clients.php", function (html) {
        $("#client-list").html(html);
    });
}


// ========================================
// LOAD RIGHT PANEL INFO
// ========================================
function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, function (html) {
        $("#client-info-content").html(html);
    });
}


// ========================================
// LOAD MESSAGES INCREMENTALLY (NO FLICKER)
// ========================================
function loadMessages(scrollBottom = false) {
    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {

        const $incoming = $(html);
        const $last = $incoming.last();
        const newLastID = parseInt($last.attr("data-msg-id"));

        if (newLastID > lastMessageID) {
            lastMessageID = newLastID;
            $("#chat-messages").append($incoming);

            if (scrollBottom) {
                const box = $("#chat-messages");
                box.scrollTop(box[0].scrollHeight);
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

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg,
        sender_type: "csr"
    }, function (res) {
        if (res.status === "ok") {
            $("#chat-input").val("");
            loadMessages(true);
        }
    }, "json");
}


// ========================================
// PREVIEW MULTIPLE FILES
// ========================================
function previewMultiple(files) {
    $("#preview-files").html("");

    files.forEach(file => {
        if (file.type.startsWith("image")) {
            const reader = new FileReader();
            reader.onload = e => {
                $("#preview-files").append(`<img src="${e.target.result}" class="preview-thumb">`);
            };
            reader.readAsDataURL(file);
        } else {
            $("#preview-files").append(`<div class="file-box">${file.name}</div>`);
        }
    });

    $("#preview-overlay").fadeIn(200);
}


// ========================================
// UPLOAD MULTIPLE MEDIA FILES
// ========================================
function uploadMedia(files) {
    const fd = new FormData();
    files.forEach((file, i) => fd.append("media[]", file));

    fd.append("client_id", currentClientID);
    fd.append("sender_type", "csr");

    selectedFiles = [];
    $("#preview-overlay").fadeOut(200);
    $("#chat-upload-media").val("");

    $.ajax({
        url: "../chat/media_upload.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (res) {
            if (res.status === "ok") {
                loadMessages(true);
            } else {
                alert(res.msg || "Upload failed");
            }
        },
        error: function (err) {
            console.error(err.responseText);
            alert("Upload error");
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
