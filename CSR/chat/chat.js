// ========================================
// SkyTruFiber CSR Chat System - FINAL
// ========================================

let activeClient = null;
let typingTimeout;
const csrUser = $("#csr-username").val();

$(document).ready(function () {

    loadClients();
    setInterval(loadClients, 4000);
    setInterval(checkTyping, 1000);
    setInterval(loadMessages, 1500);

    // Search filter
    $("#client-search").on("keyup", function () {
        let q = $(this).val().toLowerCase();
        $(".client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // Send Message Button
    $("#send-btn").click(sendMessage);

    // Enter to send
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        } else {
            updateTyping();
        }
    });

    // Upload
    $("#upload-btn").click(() => $("#chat-upload-media").click());
    $("#chat-upload-media").change(uploadMedia);

    // Close media viewer
    $("#viewer-close").click(() => $("#media-viewer").fadeOut(150));
});

// ========================================
// Load Clients
// ========================================
function loadClients() {
    $.post("chat/load_clients.php", {}, function (data) {
        $("#client-list").html(data);
    });
}

// ========================================
// Client Click Event
// ========================================
$(document).on("click", ".client-item", function (e) {

    if ($(e.target).closest(".client-icons").length) return;

    activeClient = $(this).data("id");
    $(".client-item").removeClass("active-chat");
    $(this).addClass("active-chat");

    $("#chat-client-name").text($(this).data("name"));

    $("#chat-messages").html("");
    loadMessages(true);
    loadClientInfo(activeClient);
    updateSeen();
});

// ========================================
// Load Messages
// ========================================
function loadMessages(scroll) {
    if (!activeClient) return;

    $.post("chat/load_messages.php", { client_id: activeClient }, function (html) {
        $("#chat-messages").html(html);

        if (scroll) {
            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        } else {
            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        }
    });
}

// ========================================
// Send Message
// ========================================
function sendMessage() {

    let msg = $("#chat-input").val().trim();
    if (!msg || !activeClient) return;

    $.post("chat/send_message.php", {
        client_id: activeClient,
        message: msg,
        sender_type: "csr"
    }, function () {
        $("#chat-input").val("");
        loadMessages(true);
        updateSeen();
    });
}

// ========================================
// Upload Media
// ========================================
function uploadMedia() {

    const file = $("#chat-upload-media")[0].files[0];
    if (!file || !activeClient) return;

    const fd = new FormData();
    fd.append("media", file);
    fd.append("client_id", activeClient);
    fd.append("csr", csrUser);

    $.ajax({
        url: "chat/upload_media.php",
        type: "POST",
        data: fd,
        contentType: false,
        processData: false,
        success: function () {
            $("#chat-upload-media").val("");
            loadMessages(true);
        }
    });
}

// ========================================
// Update Seen
// ========================================
function updateSeen() {
    if (!activeClient) return;
    $.post("chat/update_seen.php", { client_id: activeClient });
}

// ========================================
// Typing Indicator
// ========================================
function updateTyping() {
    if (!activeClient) return;
    $.post("chat/typing_update.php", { client_id: activeClient, user: "csr" });
}

function checkTyping() {
    if (!activeClient) return;

    $.post("chat/check_typing.php", { client_id: activeClient }, function (res) {
        if (res == "1") $("#typing-indicator").show();
        else $("#typing-indicator").hide();
    });
}

// ========================================
// Assign / Unassign Client
// ========================================
$(document).on("click", ".add-client", function (e) {
    e.stopPropagation();
    $.post("chat/assign_client.php", { client_id: $(this).data("id") }, loadClients);
});

$(document).on("click", ".remove-client", function (e) {
    e.stopPropagation();
    $.post("chat/unassign_client.php", { client_id: $(this).data("id") }, function () {
        loadClients();
        $("#client-info-content").html("<p>Select a client.</p>");
    });
});

// ========================================
// Media Preview Viewer
// ========================================
window.openMediaViewer = function (path) {
    $("#media-viewer-img").attr("src", path);
    $("#media-viewer").fadeIn(150);
};

// ========================================
// Load Right Panel
// ========================================
function loadClientInfo(id) {
    $.post("chat/load_client_info.php", { client_id: id }, function (data) {
        $("#client-info-content").html(data);
    });
}
