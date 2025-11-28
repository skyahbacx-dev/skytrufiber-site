// CHAT.JS - FINAL MESSENGER STYLE

let activeClient = null;
let previewFile = null;

// Load clients initially
loadClients();

function loadClients() {
    $.ajax({
        url: "../chat/load_clients.php",
        method: "GET",
        success: function (data) {
            $("#client-list").html(data);
        }
    });
}

// When clicking client
$(document).on("click", ".client-item", function () {
    $(".client-item").removeClass("active");
    $(this).addClass("active");

    activeClient = $(this).data("id");
    $("#chat-client-name").text($(this).data("name"));

    loadMessages();
    loadClientInfo();
});

// Load messages
function loadMessages() {
    if (!activeClient) return;

    $.ajax({
        url: "../chat/load_messages.php",
        method: "POST",
        data: { client_id: activeClient },
        success: function (data) {
            $("#chat-messages").html(data);
            scrollBottom();
        }
    });
}

function scrollBottom() {
    let chatBox = document.getElementById("chat-messages");
    chatBox.scrollTop = chatBox.scrollHeight;
}

setInterval(() => {
    if (activeClient) loadMessages();
}, 2000);

// Load client info
function loadClientInfo() {
    $.ajax({
        url: "../chat/load_client_info.php",
        method: "POST",
        data: { client_id: activeClient },
        success: function (data) {
            $("#client-info-content").html(data);
        }
    });
}

// ===================== FILE PREVIEW ============================
$("#upload-btn").on("click", () => $("#chat-upload-media").click());

$("#chat-upload-media").on("change", function () {
    previewFile = this.files[0];

    if (!previewFile) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        $("#media-preview-container").remove(); // Clear old preview

        $(".chat-input-area").before(`
            <div id="media-preview-container" class="preview-wrapper">
                <img id="preview-image" src="${e.target.result}">
                <button id="cancel-preview" class="cancel-preview">Cancel</button>
            </div>
        `);
    };
    reader.readAsDataURL(previewFile);
});

$(document).on("click", "#cancel-preview", function () {
    previewFile = null;
    $("#media-preview-container").remove();
    $("#chat-upload-media").val("");
});

// ===================== SEND MESSAGE ============================
$("#send-btn").on("click", function () {
    sendMessage();
});

$("#chat-input").keypress(function (e) {
    if (e.which === 13) sendMessage();
});

function sendMessage() {
    if (!activeClient) return;

    let messageText = $("#chat-input").val().trim();

    // If message empty & no file, do nothing
    if (!messageText && !previewFile) return;

    let formData = new FormData();
    formData.append("client_id", activeClient);
    formData.append("csr", $("#csr-username").val());
    formData.append("message", messageText);

    if (previewFile) {
        formData.append("media", previewFile);
    }

    $.ajax({
        url: "../chat/send_message.php",
        method: "POST",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function () {
            $("#chat-input").val("");
            previewFile = null;
            $("#media-preview-container").remove();
            $("#chat-upload-media").val("");
            loadMessages();
        }
    });
}

// ===================== IMAGE FULLSCREEN VIEWER ============================
$(document).on("click", ".chat-image", function () {
    const imageSrc = $(this).attr("src");

    $("body").append(`
        <div class="image-viewer" id="image-viewer">
            <span class="close-viewer" id="close-viewer">&times;</span>
            <img class="viewer-content" src="${imageSrc}">
        </div>
    `);

    $("#image-viewer").fadeIn();
});

$(document).on("click", "#close-viewer", function () {
    $("#image-viewer").remove();
});
