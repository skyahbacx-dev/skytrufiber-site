// ================================================
// CSR CHAT DASHBOARD JS — Batch 5
// ================================================

let activeClient = null;
let refreshInterval = null;

// DOM
const clientList = document.getElementById("clientList");
const chatMessages = document.getElementById("chatMessages");
const messageInput = document.getElementById("messageInput");
const sendBtn = document.getElementById("sendBtn");

// =====================================================
// INITIAL LOAD CLIENT LIST
// =====================================================
function loadClientList(query = "") {
    $.post("client_list.php", { search: query }, function (data) {
        clientList.innerHTML = data;
        attachClientEvents();
    });
}

function attachClientEvents() {
    $(".client-item").click(function () {
        let id = $(this).data("id");
        selectClient(id);
    });
}

// =====================================================
// SELECT CLIENT
// =====================================================
function selectClient(clientId) {
    activeClient = clientId;

    $(".client-item").removeClass("active");
    $(`.client-item[data-id='${clientId}']`).addClass("active");

    chatMessages.innerHTML = "Loading messages...";
    loadClientInfo(clientId);
    loadMessages();

    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(loadMessages, 2000);
}

// =====================================================
// LOAD CLIENT INFO PANEL
// =====================================================
function loadClientInfo(id) {
    $.post("client_info.php", { client_id: id }, function (res) {
        let data = JSON.parse(res);

        infoName.innerText = data.fullname;
        infoEmail.innerText = data.email;
        infoDistrict.innerText = data.district;
        infoBrgy.innerText = data.barangay;
        infoAvatar.src = data.avatar ?? "upload/default-avatar.png";

        chatName.innerText = data.fullname;
        chatStatus.innerHTML = data.is_online
            ? `<span class='status-dot online'></span> Online`
            : `<span class='status-dot offline'></span> Offline`;

        updateAssignButtons(data.assigned_csr);
    });
}

// =====================================================
// LOAD MESSAGES
// =====================================================
function loadMessages() {
    if (!activeClient) return;

    $.post("load_chat_csr.php", { client_id: activeClient }, function (res) {
        chatMessages.innerHTML = res;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}

// =====================================================
// SEND MESSAGE
// =====================================================
sendBtn.addEventListener("click", sendMessage);
messageInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    let msg = messageInput.value.trim();
    if (!msg || !activeClient) return;

    $.post("save_chat_csr.php",
        { message: msg, client_id: activeClient },
        function (res) {
            if (res === "OK") {
                messageInput.value = "";
                loadMessages();
            } else {
                console.log(res);
            }
        }
    );
}

// =====================================================
// SEARCH
// =====================================================
$("#searchInput").on("input", function () {
    loadClientList($(this).val());
});

// =====================================================
// ASSIGN / UNASSIGN CLIENT
// =====================================================
function updateAssignButtons(assigned) {
    if (!assigned) {
        $("#assignYes").show();
        $("#assignNo").hide();
    } else {
        $("#assignYes").hide();
        $("#assignNo").show();
    }
}

$("#assignYes").click(function () {
    $.post("assign_client.php", { client_id: activeClient }, function () {
        updateAssignButtons(true);
        loadClientList();
    });
});

$("#assignNo").click(function () {
    $.post("unassign_client.php", { client_id: activeClient }, function () {
        updateAssignButtons(false);
        loadClientList();
    });
});

// =====================================================
// INITIALIZE
// =====================================================
loadClientList();
