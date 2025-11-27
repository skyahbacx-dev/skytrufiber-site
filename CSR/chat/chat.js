// ============ GLOBAL STATE ============
let selectedClientId = null;
let pollingInterval = null;
let isTyping = false;
let typingTimeout = null;

// ============ INITIAL SETUP ============
$(document).ready(function () {
    loadClients();
    startPolling();

    $("#send-btn").on("click", sendMessage);

    $("#chat-input").on("keypress", function (e) {
        if (e.which === 13) {  // Enter key
            sendMessage();
        } else {
            updateTypingStatus(true);
        }
    });

    $("#upload-btn").on("click", () => $("#chat-upload-media").click());
    $("#chat-upload-media").on("change", uploadMedia);
});

// ============ AUTO REFRESH ============

function startPolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(() => {
        if (selectedClientId !== null) {
            loadMessages();
            checkTyping();
        }
        loadClients(); // keep client list fresh
    }, 2000);
}

// ============ LOAD CLIENTS ============
function loadClients() {
    $.post("load_clients.php", { csr: $("#csr-username").val() }, function (data) {
        $("#client-list").html(data);

        $(".client-item").off("click").on("click", function () {
            selectedClientId = $(this).data("id");
            $("#chat-client-name").text($(this).data("name"));
            $("#client-status").removeClass().addClass("status-dot " + $(this).data("status"));
            loadClientDetails(selectedClientId);
            loadMessages();
        });
    });
}

// ============ LOAD CLIENT DETAILS ============
function loadClientDetails(clientId) {
    $.post("client_details.php", { client_id: clientId }, function (data) {
        $("#client-info-fields").html(data);
    });
}

// ============ LOAD MESSAGES ============
function loadMessages() {
    $.post("load_messages.php", { client_id: selectedClientId }, function (data) {
        $("#chat-messages").html(data);
        $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        updateSeen();
    });
}

// ============ SEND MESSAGE ============
function sendMessage() {
    const message = $("#chat-input").val().trim();
    if (message === "" || selectedClientId === null) return;

    $.post("send_message.php", {
        client_id: selectedClientId,
        csr: $("#csr-username").val(),
        message: message
    }, function () {
        $("#chat-input").val("");
        loadMessages();
        updateTypingStatus(false);
    });
}

// ============ SEEN UPDATE ============
function updateSeen() {
    $.post("seen_update.php", { client_id: selectedClientId });
}

// ============ TYPING ============

function updateTypingStatus(status) {
    if (!selectedClientId) return;

    $.post("typing_update.php", {
        client_id: selectedClientId,
        csr: $("#csr-username").val(),
        typing: status ? 1 : 0
    });

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        $.post("typing_update.php", {
            client_id: selectedClientId,
            csr: $("#csr-username").val(),
            typing: 0
        });
    }, 2000);
}

function checkTyping() {
    $.post("check_typing.php", { client_id: selectedClientId }, function (data) {
        if (data.trim() === "1") {
            $("#typing-indicator").show();
        } else {
            $("#typing-indicator").hide();
        }
    });
}

// ============ MEDIA UPLOAD ============
function uploadMedia() {
    const file = $("#chat-upload-media")[0].files[0];
    if (!file || !selectedClientId) return;

    const formData = new FormData();
    formData.append("media", file);
    formData.append("client_id", selectedClientId);
    formData.append("csr", $("#csr-username").val());

    $.ajax({
        url: "media_upload.php",
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function () {
            $("#chat-upload-media").val("");
            loadMessages();
        }
    });
}
