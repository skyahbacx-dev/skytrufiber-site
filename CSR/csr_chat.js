let activeClient = null;
let refreshInterval = null;

// DOM
const clientList = document.getElementById("clientList");
const chatMessages = document.getElementById("chatMessages");
const messageInput = document.getElementById("messageInput");
const fileInput = document.getElementById("fileInput");
const sendBtn = document.getElementById("sendBtn");

// ==========================================
// LOAD CLIENT LIST
// ==========================================
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

// ==========================================
// SELECT CLIENT
// ==========================================
function selectClient(clientId) {
    activeClient = clientId;

    $(".client-item").removeClass("active");
    $(`.client-item[data-id="${clientId}"]`).addClass("active");

    chatMessages.innerHTML = "Loading messages...";
    loadClientInfo(clientId);
    loadMessages();

    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(loadMessages, 2000);
}

// ==========================================
// LOAD MESSAGES
// ==========================================
function loadMessages() {
    if (!activeClient) return;

    $.post("load_chat_csr.php", { client_id: activeClient }, function (data) {
        chatMessages.innerHTML = data;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}

// ==========================================
// SEND TEXT MESSAGE
// ==========================================
sendBtn.addEventListener("click", sendMessage);

messageInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    let msg = messageInput.value.trim();
    if (!msg || !activeClient) return;

    $.post("save_chat_csr.php", { message: msg, client_id: activeClient }, function (res) {
        if (res === "OK") {
            messageInput.value = "";
            loadMessages();
        }
    });
}

// ==========================================
// FILE UPLOAD & PREVIEW
// ==========================================
fileInput.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function () {
        const preview = confirm("Send this file?");
        if (preview) uploadFile(file);
    };
    reader.readAsDataURL(file);
});

function uploadFile(file) {
    let fd = new FormData();
    fd.append("file", file);
    fd.append("client_id", activeClient);

    $.ajax({
        url: "upload_media_csr.php",
        type: "POST",
        data: fd,
        contentType: false,
        processData: false,
        success: function (res) {
            console.log(res);
            loadMessages();
        }
    });
}

// ==========================================
// SEARCH
// ==========================================
$("#searchInput").on("input", function () {
    loadClientList($(this).val());
});

// Initial load
loadClientList();
