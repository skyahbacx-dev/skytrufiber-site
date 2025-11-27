// ========================================
// SkyTruFiber CSR Chat System - chat.js
// with full UI effects package
// ========================================

let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;

const notifSound = new Audio("../sound/notif.mp3");

// Toast popup
function showToast(text) {
    let toast = $("<div class='toast-alert'>" + text + "</div>");
    $("body").append(toast);
    setTimeout(() => toast.addClass("show"), 20);
    setTimeout(() => toast.removeClass("show"), 2500);
    setTimeout(() => toast.remove(), 3000);
}

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
        } else {
            $.post("../chat/typing_update.php", {
                client_id: currentClientID,
                typing: 1,
                user: "csr"
            });
        }
    });

    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(() => uploadMedia());

    $(document).on("click", ".client-item", function (e) {
        if ($(e.target).closest(".client-icons").length) return;

        $(".client-item").removeClass("active-chat");
        $(this).addClass("active-chat");

        currentClientID = $(this).data("id");
        let name = $(this).data("name");
        $("#chat-client-name").text(name);

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(() => loadMessages(false), 1500);
    });

    $(document).on("click", ".add-client", function (e) {
        e.stopPropagation();
        assignClient($(this).data("id"));
    });

    $(document).on("click", ".remove-client", function (e) {
        e.stopPropagation();
        unassignClient($(this).data("id"));
    });
});

// ========================================
// LOAD CLIENT LIST
// ========================================
function loadClients() {
    $.post("../chat/load_clients.php", {}, function (html) {
        $("#client-list").html(html);
    });
}

// ========================================
// LOAD CLIENT INFO RIGHT SIDE
// ========================================
function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, function (html) {
        $("#client-info-content").html(html);
    });
}

// ========================================
// LOAD MESSAGES W/ EFFECTS + SOUND
// ========================================
let lastMessageID = null;

function loadMessages(scrollBottom) {
    if (!currentClientID) return;

    $.ajax({
        url: "../chat/load_messages.php",
        type: "POST",
        data: { client_id: currentClientID },
        success: function (html) {

            let messageBox = $("#chat-messages");
            messageBox.html(html);

            let newLast = $(".chat-bubble").last().data("mid");
            if (lastMessageID && newLast && newLast !== lastMessageID) {
                notifSound.play();
                showToast("New message received");
            }
            lastMessageID = newLast;

            if (scrollBottom) {
                messageBox.animate({ scrollTop: messageBox[0].scrollHeight }, 350);
            } else {
                messageBox.scrollTop(messageBox[0].scrollHeight);
            }
        }
    });
}

// ========================================
// SEND MESSAGE
// ========================================
function sendMessage() {
    let msg = $("#chat-input").val().trim();
    if (!msg || !currentClientID) return;

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg,
        sender_type: "csr"
    }, function () {
        $("#chat-input").val("");
        loadMessages(true);
    });
}

// ========================================
// MEDIA UPLOAD
// ========================================
function uploadMedia() {
    const fileInput = $("#chat-upload-media")[0];
    if (!fileInput.files.length) return;

    const formData = new FormData();
    formData.append("media", fileInput.files[0]);
    formData.append("client_id", currentClientID);
    formData.append("csr", $("#csr-username").val());

    $.ajax({
        url: "../chat/upload_media.php",
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
// ASSIGN / UNASSIGN CLIENT
// ========================================
function assignClient(id) {
    $.post("../chat/assign_client.php", { client_id: id }, function () {
        loadClients();
        loadClientInfo(id);
        showToast("Client assigned to you");
    });
}

function unassignClient(id) {
    $.post("../chat/unassign_client.php", { client_id: id }, function () {
        loadClients();
        $("#client-info-content").html("<p>Select a client.</p>");
        showToast("Client unassigned");
    });
}
