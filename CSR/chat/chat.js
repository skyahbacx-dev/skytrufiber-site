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
// Lightbox Preview
$(document).on("click", ".media-thumb", function () {
    const src = $(this).attr("src");
    $("#lightbox-image").attr("src", src);
    $("#lightbox-overlay").fadeIn(200);
});

$("#lightbox-close, #lightbox-overlay").on("click", function () {
    $("#lightbox-overlay").fadeOut(200);
});
let selectedMediaFiles = [];

// Multi-file selection
$("#chat-upload-media").on("change", function () {
    selectedMediaFiles = Array.from(this.files);
    $("#media-preview-grid").html("");

    selectedMediaFiles.forEach((file, index) => {
        let reader = new FileReader();
        reader.onload = (e) => {
            const thumb = `
              <div class="preview-item">
                  <img src="${e.target.result}" data-index="${index}" class="preview-thumb">
                  <button class="remove-thumb" data-remove="${index}">✕</button>
              </div>`;
            $("#media-preview-grid").append(thumb);
        };
        reader.readAsDataURL(file);
    });

    $("#media-preview-modal").fadeIn(200);
});

// REMOVE item from preview
$(document).on("click", ".remove-thumb", function () {
    const index = $(this).data("remove");
    selectedMediaFiles.splice(index, 1);
    $(this).parent().remove();
});

// OPEN fullscreen viewer
$(document).on("click", ".preview-thumb", function () {
    $("#viewer-img").attr("src", $(this).attr("src"));
    $("#image-viewer").fadeIn(150);
});

// Close viewer
$("#viewer-close").click(() => $("#image-viewer").fadeOut(150));

// CANCEL
$("#media-cancel-btn").click(() => {
    selectedMediaFiles = [];
    $("#media-preview-modal").fadeOut(200);
});

// SEND
$("#media-send-btn").click(() => {
    $("#media-preview-modal").fadeOut(200);
    $("#upload-loading").fadeIn(150);

    selectedMediaFiles.forEach((file, index) => {
        uploadConfirmedMedia(file, index === selectedMediaFiles.length - 1);
    });
});

// UPLOAD
function uploadConfirmedMedia(file, last) {
    const formData = new FormData();
    formData.append("media", file);
    formData.append("client_id", currentClientId);
    formData.append("csr", csrUser);

    $.ajax({
        url: "chat/upload_media_csr.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: () => {
            if (last) {
                $("#upload-loading").fadeOut(150);
                reloadMessages();
                setTimeout(scrollToBottom, 200);
            }
        }
    });
}

function scrollToBottom() {
    $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
}
