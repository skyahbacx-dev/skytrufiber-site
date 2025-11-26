// ================== GLOBAL =====================
let activeClient = null;
let typingTimer = null;
const chatMessages = $("#chatMessages");
const clientList = $("#clientList");

// ================== LOAD CLIENT LIST =====================
function loadClients(query = "") {
    $.post("client_list.php", { search: query }, function (response) {
        clientList.html(response);

        $(".client-item").on("click", function () {
            const clientId = $(this).data("id");
            selectClient(clientId);
        });
    });
}

// search filter
$("#searchInput").on("input", function () {
    loadClients($(this).val());
});

// ================== SELECT CLIENT =====================
function selectClient(clientId) {
    activeClient = clientId;
    loadClientInfo(clientId);
    loadMessages(clientId);
}

// Load details
function loadClientInfo(clientId) {
    $.post("client_info.php", { client_id: clientId }, function (data) {
        let info = JSON.parse(data);

        $("#chatName").text(info.full_name);
        $("#infoName").text(info.full_name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);

        $("#statusDot").removeClass("online offline")
            .addClass(info.is_online ? "online" : "offline");
        $("#chatStatus").text(info.is_online ? "Online" : "Offline");

        if (info.assigned === "yes") {
            $("#assignYes").hide();
            $("#assignNo").show();
        } else {
            $("#assignYes").show();
            $("#assignNo").hide();
        }
    });
}

// ================== ASSIGN / UNASSIGN =====================
$("#assignYes").on("click", function () {
    $.post("assign_client.php", { client_id: activeClient }, function () {
        $("#assignYes").hide();
        $("#assignNo").show();
        loadClients();
    });
});

$("#assignNo").on("click", function () {
    $.post("unassign_client.php", { client_id: activeClient }, function () {
        $("#assignYes").show();
        $("#assignNo").hide();
        loadClients();
    });
});

// ================== LOAD MESSAGES =====================
function loadMessages(clientId) {
    $.post("load_chat_csr.php", { client_id: clientId }, function (response) {
        chatMessages.html(response);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    });
}

// ================== SEND MESSAGE =====================
$("#sendBtn").on("click", function () {
    sendMessage();
});

$("#messageInput").keypress(function (e) {
    if (e.which === 13) sendMessage();
});

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (msg === "" || !activeClient) return;

    $.post("save_chat_csr.php", { client_id: activeClient, message: msg }, function () {
        $("#messageInput").val("");
        loadMessages(activeClient);
    });
}

// ================== POLLING =====================
setInterval(function () {
    if (activeClient) loadMessages(activeClient);
}, 2000);

// ================== SIDEBAR TOGGLE =====================
function toggleSidebar() {
    $("#infoPanel").toggleClass("show");
}
window.toggleSidebar = toggleSidebar;

// ================== INITIAL CALL =====================
loadClients();
