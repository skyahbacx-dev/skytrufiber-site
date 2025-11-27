$(document).ready(function () {

    let currentClient = null;
    const csrUser = $("#csr-username").val();

    // Load clients initially & refresh every 4s
    loadClients();
    setInterval(loadClients, 4000);

    function loadClients() {
        $.post("chat/load_clients.php", {}, function (response) {
            $("#client-list").html(response);
        });
    }

    // When selecting a client
    $(document).on("click", ".client-item", function () {
        currentClient = $(this).data("id");
        $(".client-item").removeClass("active");
        $(this).addClass("active");

        $("#chat-client-name").text($(this).data("name"));

        loadMessages();
        loadClientInfo();

        setInterval(loadMessages, 3000);
    });

    // Load chat messages
    function loadMessages() {
        if (!currentClient) return;

        $.get("chat/load_messages.php", { client_id: currentClient }, function (data) {
            $("#chat-messages").html(data);
            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        });
    }

    // Send message
    $("#send-btn").click(function () {
        sendText();
    });

    $("#chat-input").keypress(function (e) {
        if (e.which === 13) sendText();
    });

    function sendText() {
        const message = $("#chat-input").val().trim();
        if (message === "" || !currentClient) return;

        $.post("chat/send_message.php", {
            client_id: currentClient,
            message: message,
            sender_type: "csr"
        }, function () {
            $("#chat-input").val("");
            loadMessages();
        });
    }

    // Upload media
    $("#upload-btn").click(function () {
        $("#chat-upload-media").click();
    });

    $("#chat-upload-media").change(function () {
        let formData = new FormData();
        formData.append("media", $("#chat-upload-media")[0].files[0]);
        formData.append("client_id", currentClient);
        formData.append("csr", csrUser);

        $.ajax({
            url: "chat/upload_media_client.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function () {
                loadMessages();
            }
        });
    });

    // Load client information
    function loadClientInfo() {
        if (!currentClient) return;
        $.post("chat/load_client_info.php", { client_id: currentClient }, function (data) {
            $("#client-info-content").html(data);
        });
    }

    // Lock / Remove
    $(document).on("click", ".btn-lock", function () {
        $.post("chat/lock_client.php", { client_id: currentClient }, function () {
            loadClients();
            loadClientInfo();
        });
    });

    $(document).on("click", ".btn-remove", function () {
        if (confirm("Remove this client & chat history?")) {
            $.post("chat/remove_client.php", { client_id: currentClient }, function () {
                location.reload();
            });
        }
    });

});

// IMAGE MODAL VIEWER
function openImageModal(src) {
    $("#imageModal img").attr("src", src);
    $("#imageModal").fadeIn(200);
}

function closeImageModal() {
    $("#imageModal").fadeOut(200);
}
