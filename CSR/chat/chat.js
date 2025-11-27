// =============================
//  CSR CHAT JAVASCRIPT HANDLER
// =============================

// Refresh intervals
let refreshMessages;
let refreshTyping;

// Load clients list immediately when page loads
$(document).ready(function () {
    loadClients();

    $("#searchClient").on("keyup", function(){
        const value = $(this).val().toLowerCase();
        $(".client-item").filter(function(){
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});

// =============================
// LOAD CLIENT LIST
// =============================
function loadClients() {
    $.ajax({
        url: "/CSR/chat/load_clients.php",
        type: "POST",
        success: function (data) {
            $("#client-list").html(data);
        },
        error: function () {
            console.log("ERROR: load_clients.php failed");
        }
    });
}


// =============================
// SELECT A CLIENT TO OPEN CHAT
// =============================
function openChat(client_id) {

    $("#selected-client").val(client_id);

    $("#chat-messages").html(
        '<div style="text-align:center;margin-top:30px;color:#999;">Loading messages...</div>'
    );

    loadMessages();

    clearInterval(refreshMessages);
    refreshMessages = setInterval(loadMessages, 1500);

    clearInterval(refreshTyping);
    refreshTyping = setInterval(checkTyping, 1200);
}


// =============================
// LOAD CHAT MESSAGES
// =============================
function loadMessages() {
    const client_id = $("#selected-client").val();

    if (!client_id) return;

    $.ajax({
        url: "/CSR/chat/load_messages.php",
        type: "POST",
        data: { client_id: client_id },
        success: function (data) {
            $("#chat-messages").html(data);
            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        },
        error: function () {
            console.log("ERROR: load_messages.php failed");
        }
    });
}


// =============================
// SEND TEXT MESSAGE
// =============================
function sendMessage() {
    const message = $("#message-box").val();
    const client_id = $("#selected-client").val();

    if (!message.trim() || !client_id) return;

    $.ajax({
        url: "/CSR/chat/send_message.php",
        type: "POST",
        data: { client_id: client_id, message: message },
        success: function () {
            $("#message-box").val("");
            loadMessages();
        },
        error: function () {
            console.log("ERROR: send_message.php failed");
        }
    });
}


// =============================
// TYPING INDICATOR (CSR typing)
// =============================
$("#message-box").on("input", function () {
    const client_id = $("#selected-client").val();

    $.post("/CSR/chat/typing_update.php", {
        client_id: client_id,
        typing: 1
    });

    setTimeout(function () {
        $.post("/CSR/chat/typing_update.php", {
            client_id: client_id,
            typing: 0
        });
    }, 1200);
});


// =============================
// CHECK IF CLIENT IS TYPING
// =============================
function checkTyping() {
    const client_id = $("#selected-client").val();
    if (!client_id) return;

    $.ajax({
        url: "/CSR/chat/check_typing.php",
        type: "POST",
        data: { client_id: client_id },
        success: function (response) {
            if (response.trim() === "1") {
                $("#typing-indicator").show();
            } else {
                $("#typing-indicator").hide();
            }
        },
        error: function () {
            console.log("ERROR: typing check failed");
        }
    });
}


// =============================
// MEDIA UPLOAD
// =============================
function triggerUpload() {
    $("#media-input").click();
}

function uploadMedia() {
    const client_id = $("#selected-client").val();
    const file = $("#media-input")[0].files[0];

    if (!file || !client_id) return;

    let formData = new FormData();
    formData.append("file", file);
    formData.append("client_id", client_id);

    $.ajax({
        url: "/CSR/chat/upload_media.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function () {
            loadMessages();
        },
        error: function () {
            console.log("ERROR: upload_media.php failed");
        }
    });
}
