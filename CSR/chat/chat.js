$(document).ready(function () {

    let selectedClient = null;
    let audioNotify = new Audio("../sound/new_message.wav");

    loadClients();
    setInterval(loadClients, 3000);

    function loadClients() {
        $.post("../chat/load_clients.php", {}, function (data) {
            $("#client-list").html(data);
        });
    }

    $(document).on("click", ".client-item", function () {
        selectedClient = $(this).data("id");
        $("#chat-client-name").text($(this).find("strong").text());
        $("#chat-messages").html("");
        loadMessages();
        pollMessages();
    });

    function pollMessages() {
        setInterval(function () {
            if (selectedClient !== null) {
                loadMessages();
            }
        }, 2000);
    }

    function loadMessages() {
        $.post("../chat/load_messages.php", { client_id: selectedClient }, function (res) {
            let data = JSON.parse(res);
            $("#chat-messages").html(data.messages);
            $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);

            if (data.new_message) {
                audioNotify.play();
                showToast("New message received");
            }

            updateStatusIndicators(data);
        });
    }

    function updateStatusIndicators(data) {
        if (data.is_online == 1) {
            $("#client-status").removeClass("offline").addClass("online");
        } else {
            $("#client-status").removeClass("online").addClass("offline");
        }

        $("#typing-indicator").toggle(data.is_typing == 1);
    }

    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) sendMessage();
        updateTyping(1);
    });

    function sendMessage() {
        let text = $("#chat-input").val().trim();
        if (!text || selectedClient == null) return;

        $.post("../chat/send_message.php", {
            client_id: selectedClient,
            message: text,
            sender_type: "csr"
        }, function () {
            $("#chat-input").val("");
            updateTyping(0);
            loadMessages();
        });
    }

    function updateTyping(status) {
        $.post("../chat/typing_update.php", {
            client_id: selectedClient,
            typing: status
        });
    }

    function showToast(message) {
        let t = $("<div class='toast-msg'>" + message + "</div>");
        $("body").append(t);
        setTimeout(() => t.fadeOut(400, () => t.remove()), 2000);
    }

});
