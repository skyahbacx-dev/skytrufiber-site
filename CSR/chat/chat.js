$(document).ready(function () {

    let currentClient = null;
    let typingTimeout = null;

    loadClients();

    function loadClients() {
        $.post("../chat/load_clients.php", {}, function (data) {
            $("#client-list").html(data);
        });
    }

    $(document).on("click", ".client-item", function () {
        currentClient = $(this).data("id");
        $(".client-item").removeClass("active");
        $(this).addClass("active");
        loadMessages();
        loadClientInfo();
    });

    function loadMessages() {
        if (!currentClient) return;

        $.post("../chat/load_messages.php", { client_id: currentClient }, function (res) {
            let messages = JSON.parse(res);
            $("#chat-messages").html("");

            messages.forEach(msg => {
                let bubble = "";

                if (msg.media_path) {
                    if (msg.media_type === "image") {
                        bubble += `
                            <div class="chat-message ${msg.sender_type}">
                                <img src="/${msg.media_path}" class="chat-image previewable">
                                <div class="msg-time">${formatTime(msg.created_at)}</div>
                            </div>
                        `;
                    } else {
                        bubble += `
                            <div class="chat-message ${msg.sender_type}">
                                <button class="download-file-btn" onclick="window.open('/${msg.media_path}')">
                                    📎 Download File
                                </button>
                                <div class="msg-time">${formatTime(msg.created_at)}</div>
                            </div>
                        `;
                    }
                } else {
                    bubble += `
                        <div class="chat-message ${msg.sender_type}">
                            ${msg.message}
                            <div class="msg-time">${formatTime(msg.created_at)}</div>
                        </div>
                    `;
                }

                $("#chat-messages").append(bubble);
            });

            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
        });
    }

    function formatTime(datetime) {
        return new Date(datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    $("#send-btn").click(sendMessage);

    $("#chat-input").keypress(function (e) {
        if (e.which === 13) sendMessage();
        sendTyping();
    });

    function sendMessage() {
        let text = $("#chat-input").val().trim();
        if (text === "" || !currentClient) return;

        $.post("../chat/send_message.php", { message: text, client_id: currentClient }, function () {
            $("#chat-input").val("");
            loadMessages();
        });
    }

    function sendTyping() {
        if (!currentClient) return;
        $.post("../chat/update_typing.php", { client_id: currentClient }, function () {});
    }

    // File upload preview
    $("#file-upload").change(function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        $("#previewModal").show();

        reader.onload = function (e) {
            $("#previewImg").attr("src", e.target.result);
        };

        reader.readAsDataURL(file);
    });

    $("#uploadConfirm").click(function () {
        let formData = new FormData();
        formData.append("client_id", currentClient);
        formData.append("media", $("#file-upload")[0].files[0]);

        $.ajax({
            url: "../chat/media_upload.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function () {
                $("#previewModal").hide();
                $("#file-upload").val("");
                loadMessages();
            }
        });
    });

    $("#uploadCancel").click(function () {
        $("#previewModal").hide();
        $("#file-upload").val("");
    });

    // OPEN FULL IMAGE
    $(document).on("click", ".previewable", function () {
        $("#fullImageModal").show();
        $("#fullImage").attr("src", $(this).attr("src"));
    });

    $("#closeFullImage").click(function () {
        $("#fullImageModal").hide();
    });

});
