// ================= GLOBAL VARIABLES =================
let lastMessageID = 0;
let isTyping = false;
let typingTimer;

// Auto-refresh messages every second
setInterval(loadMessages, 1000);
setInterval(checkTypingStatus, 1000);

// ======================================================
// LOAD CHAT MESSAGES
// ======================================================
function loadMessages() {
    $.post("load_messages_client.php", { client_id: clientID }, function(data) {
        const container = $("#support-messages");
        container.html(data);

        if (data.trim() !== "") {
            scrollToBottom();
        }
    });
}

// Auto scroll to bottom
function scrollToBottom() {
    const messages = document.getElementById("support-messages");
    messages.scrollTop = messages.scrollHeight;
}

// ======================================================
// SEND MESSAGE
// ======================================================
function sendMessage() {
    let message = $("#message-box").val().trim();
    if (message === "") return;

    $.post("send_message_client.php", {
        client_id: clientID,
        message: message,
        sender_type: "CLIENT"
    }, function() {
        $("#message-box").val("");
        loadMessages();
        scrollToBottom();
        updateTyping(false);
    });
}

// Enter key send
$("#message-box").keyup(function(e) {
    updateTyping(true);
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => updateTyping(false), 1000);

    if (e.keyCode === 13) {
        sendMessage();
    }
});

// ======================================================
// UPLOAD MEDIA
// ======================================================
function triggerUpload() {
    $("#media-upload").click();
}

function uploadMedia() {
    let file = $("#media-upload")[0].files[0];
    if (!file) return;

    let formData = new FormData();
    formData.append("media", file);
    formData.append("client_id", clientID);

    $.ajax({
        url: "upload_media_client.php",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function() {
            loadMessages();
            scrollToBottom();
        }
    });
}

// ======================================================
// TYPING STATUS
// ======================================================
function updateTyping(status) {
    $.post("typing_update_client.php", {
        client_id: clientID,
        typing: status
    });
}

function checkTypingStatus() {
    $.post("check_typing_client.php", { client_id: clientID }, function(data) {
        if (data === "1") {
            $("#typing-indicator").show();
        } else {
            $("#typing-indicator").hide();
        }
    });
}

// ======================================================
// UPDATE SEEN STATUS
// ======================================================
function updateSeen() {
    $.post("update_read.php", { client_id: clientID });
}

setInterval(updateSeen, 3000);
