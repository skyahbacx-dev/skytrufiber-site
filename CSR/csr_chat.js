let selectedClient = null;
let assignedCSR = null;
let pollInterval = null;

// LOAD CLIENTS
function loadClients() {
    $.get("load_clients.php", function(data) {
        $("#clientList").html(data);
    });
}
loadClients();

// SEARCH
$("#searchInput").on("keyup", function () {
    $.get("load_clients.php?search=" + this.value, function (data) {
        $("#clientList").html(data);
    });
});

// SELECT CLIENT
function selectClient(id, name, assignedTo) {
    selectedClient = id;
    assignedCSR = assignedTo;

    $("#chatName").text(name);
    $("#chatMessages").html("");

    updateAssignButtons();

    loadMessages();
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(loadMessages, 2000);
}

// LOAD MESSAGES
function loadMessages() {
    if (!selectedClient) return;

    $.get("update_read.php?client_id=" + selectedClient, function (messages) {
        $("#chatMessages").html("");

        messages.forEach(msg => {
            const bubble = `
            <div class="msg-row ${msg.sender_type}">
                <img class="msg-avatar" src="upload/default-avatar.png">
                <div class="bubble-wrapper">
                    <div class="bubble">${msg.message || ""}</div>
                    <div class="meta">${msg.created_at}</div>
                </div>
            </div>`;
            $("#chatMessages").append(bubble);
        });

        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

// SEND MESSAGE
$("#sendBtn").on("click", sendMessage);
$("#messageInput").on("keypress", e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    if (!selectedClient) return;
    let message = $("#messageInput").val().trim();
    if (message === "") return;

    let formData = new FormData();
    formData.append("message", message);
    formData.append("client_id", selectedClient);
    formData.append("csr_fullname", csrFullname);

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: loadMessages
    });

    $("#messageInput").val("");
}

// ----------------------- ASSIGN SYSTEM -----------------------
function updateAssignButtons() {
    const isMe = assignedCSR === csrUser;
    const unassigned = assignedCSR === "" || assignedCSR === null;

    $("#assignBtn").hide();
    $("#unassignBtn").hide();
    $("#lockedBtn").hide();

    if (unassigned) $("#assignBtn").show();
    else if (isMe) $("#unassignBtn").show();
    else $("#lockedBtn").show();
}

$("#assignBtn").on("click", function () {
    $.post("assign_client.php", { client_id: selectedClient }, () => {
        assignedCSR = csrUser;
        updateAssignButtons();
        loadClients();
    });
});

$("#unassignBtn").on("click", function () {
    $.post("unassign_client.php", { client_id: selectedClient }, () => {
        assignedCSR = "";
        updateAssignButtons();
        loadClients();
    });
});

// INFO PANEL
function toggleClientInfo() {
    $("#clientInfoPanel").toggleClass("open");
}
