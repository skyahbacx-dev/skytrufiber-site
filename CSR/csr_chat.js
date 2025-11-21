// ==================== GLOBAL ====================
let selectedClient = null;
let assignedCSR = null;
let chatInterval = null;
let unreadInterval = null;
let canSend = false;

// ==================== LOAD CLIENT LIST ====================
function loadClients(query = "") {
    $.get("client_list.php?search=" + query, data => {
        $("#clientList").html(data);
    });
}

$("#searchInput").on("keyup", function () {
    loadClients($(this).val());
});

// ==================== SELECT CLIENT ====================
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedCSR = assigned;

    $("#chatName").text(name);
    $("#chatMessages").html("");
    $("#messageInput").val("");

    updateAssignButtons();
    loadChat();
    startChatPolling();

    $("#chatPanel").show();
}

// ==================== ASSIGN / UNASSIGN ====================
function updateAssignButtons() {
    const btnHolder = $("#assignControls");
    btnHolder.empty();

    if (!selectedClient) return;

    if (!assignedCSR) {
        btnHolder.html(`<button class="assign-btn" onclick="assignClient()">+</button>`);
        canSend = false;
    } else if (assignedCSR === csrUser) {
        btnHolder.html(`<button class="unassign-btn" onclick="unassignClient()">−</button>`);
        canSend = true;
    } else {
        btnHolder.html(`<button class="lock-btn" disabled>🔒</button>`);
        canSend = false;
    }
}

function assignClient() {
    $.post("assign_client.php", { client_id: selectedClient }, res => {
        loadClients();
        updateAssignButtons();
        alert("Client assigned successfully.");
    });
}

function unassignClient() {
    $.post("unassign_client.php", { client_id: selectedClient }, res => {
        loadClients();
        updateAssignButtons();
        alert("Client unassigned.");
    });
}

// ==================== LOAD CHAT ====================
function loadChat() {
    if (!selectedClient) return;

    $.getJSON("update_read.php?client_id=" + selectedClient, messages => {
        $("#chatMessages").empty();

        messages.forEach(msg => {
            let bubbleClass = msg.sender_type === "csr" ? "csr-bubble" : "client-bubble";
            let seenMark = msg.sender_type === "csr" && msg.seen === 1
                ? `<span class='seen-check'>✔✔</span>` : "";

            // Media
            let media = "";
            if (msg.media_type === "image") {
                media = `<img src="${msg.media_url}" class="chat-img" onclick="openMedia('${msg.media_url}')">`;
            }

            $("#chatMessages").append(`
                <div class="chat-row ${bubbleClass}">
                    <div class="bubble-text">${msg.message || ""}</div>
                    ${media}
                    <div class="timestamp">${msg.created_at} ${seenMark}</div>
                </div>
            `);
        });

        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

// ==================== SEND MESSAGE ====================
$("#sendBtn").on("click", sendMessage);
$("#messageInput").on("keypress", e => {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    if (!canSend || !selectedClient) {
        alert("You cannot send messages unless the client is assigned to you.");
        return;
    }

    let text = $("#messageInput").val().trim();
    let files = $("#fileInput")[0].files;

    if (!text && files.length === 0) return;

    let formData = new FormData();
    formData.append("client_id", selectedClient);
    formData.append("message", text);
    formData.append("csr_fullname", csrFullname);

    if (files.length > 0) {
        for (let i = 0; i < files.length; i++)
            formData.append("files[]", files[i]);
    }

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: () => {
            $("#previewArea").empty();
            $("#fileInput").val("");
            $("#messageInput").val("");
            loadChat();
        }
    });
}

// ==================== IMAGE PREVIEW ====================
$("#fileInput").on("change", function () {
    $("#previewArea").empty();
    [...this.files].forEach(file => {
        $("#previewArea").append(
            `<img src="${URL.createObjectURL(file)}" class="preview-img">`
        );
    });
});

// ==================== MEDIA MODAL ====================
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").fadeIn(200);
}
$("#closeMediaModal").on("click", () => $("#mediaModal").fadeOut(200));

// ==================== CLIENT INFO SLIDE ====================
function toggleClientInfo() {
    $("#clientInfoPanel").toggleClass("open");
}

// ==================== POLLING ====================
function startChatPolling() {
    clearInterval(chatInterval);
    chatInterval = setInterval(loadChat, 2500);
}

setInterval(() => {
    loadClients();
}, 6000);

// ==================== INIT ====================
$(document).ready(() => {
    loadClients();
});
