/*******************************************************
 * CSR CHAT JAVASCRIPT — FULL FILE (DO NOT SHORTEN)
 *******************************************************/

let selectedClientId = null;
let refreshInterval = null;

// DOM elements
const clientListContainer = document.getElementById("clientList");
const chatMessages = document.getElementById("chatMessages");
const chatName = document.getElementById("chatName");
const chatAvatar = document.getElementById("chatAvatar");
const chatStatus = document.getElementById("chatStatus");
const sendBtn = document.getElementById("sendBtn");
const messageInput = document.getElementById("messageInput");
const fileInput = document.getElementById("fileInput");
const previewArea = document.getElementById("previewArea");
const clientInfoPanel = document.getElementById("clientInfoPanel");


// -------------------------------------------------------------------
// Load list of clients assigned to CSR
// -------------------------------------------------------------------
function loadClientsCSR() {
    $.ajax({
        url: "load_clients_csr.php",
        type: "GET",
        dataType: "json",
        success: function (clients) {
            clientListContainer.innerHTML = "";
            clients.forEach(client => {
                const div = document.createElement("div");
                div.className = "client-item";
                div.setAttribute("data-id", client.id);

                div.innerHTML = `
                    <img src="${client.avatar || 'upload/default-avatar.png'}" class="client-avatar">
                    <div class="client-content">
                        <div class="client-name">${client.fullname}</div>
                        <div class="client-sub">${client.status}</div>
                    </div>
                `;

                div.addEventListener("click", () => loadMessages(client.id, div, client));
                clientListContainer.appendChild(div);
            });
        }
    });
}
loadClientsCSR();


// -------------------------------------------------------------------
// Load all chat messages for a client
// -------------------------------------------------------------------
function loadMessages(clientId, element, clientData) {

    selectedClientId = clientId;

    // highlight active client
    document.querySelectorAll(".client-item").forEach(el => el.classList.remove("active-client"));
    element.classList.add("active-client");

    // Update header details
    chatName.innerText = clientData.fullname;
    chatAvatar.src = clientData.avatar || "upload/default-avatar.png";

    $.ajax({
        url: `load_chat_csr.php?client_id=${clientId}`,
        type: "GET",
        dataType: "json",
        success: function (messages) {

            chatMessages.innerHTML = "";

            messages.forEach(msg => {
                renderMessage(msg);
            });

            scrollToBottom();
        }
    });

    // start realtime refresh
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(() => reloadMessagesSilent(clientId), 2000);
}


// -------------------------------------------------------------------
// Silent reload without clearing or scrolling
// -------------------------------------------------------------------
function reloadMessagesSilent(clientId) {

    $.ajax({
        url: `load_chat_csr.php?client_id=${clientId}`,
        type: "GET",
        dataType: "json",
        success: function (messages) {

            chatMessages.innerHTML = "";
            messages.forEach(msg => renderMessage(msg));
            scrollToBottom();
        }
    });
}


// -------------------------------------------------------------------
// Render individual message bubble
// -------------------------------------------------------------------
function renderMessage(msg) {

    const row = document.createElement("div");
    row.className = `msg-row ${msg.sender_type}`;

    let bubbleContent = `
        <div class="bubble-wrapper">
            <div class="bubble">${msg.message ? msg.message : ""}</div>
            <div class="meta">${msg.created_at}</div>
        </div>
    `;

    // IMAGE or VIDEO
    if (msg.media_path) {
        bubbleContent = `
        <div class="bubble-wrapper">
            <div class="bubble">${msg.message ? msg.message : ""}</div>
            <img src="${msg.media_path}" class="file-img" onclick="openMedia('${msg.media_path}')">
            <div class="meta">${msg.created_at}</div>
        </div>`;
    }

    row.innerHTML = bubbleContent;
    chatMessages.appendChild(row);
}


// -------------------------------------------------------------------
// Preview area for attachments
// -------------------------------------------------------------------
fileInput.addEventListener("change", () => {
    previewArea.innerHTML = "";

    [...fileInput.files].forEach(file => {
        const reader = new FileReader();
        reader.onload = function (e) {
            const div = document.createElement("div");
            div.className = "preview-thumb";
            div.innerHTML = `<img src="${e.target.result}">`;
            previewArea.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});


// -------------------------------------------------------------------
// Sending messages to chat backend
// -------------------------------------------------------------------
sendBtn.addEventListener("click", sendMessage);

function sendMessage() {
    if (!selectedClientId) {
        alert("Select a client first.");
        return;
    }

    const text = messageInput.value.trim();
    const files = fileInput.files;

    if (!text && files.length === 0) return;

    const formData = new FormData();
    formData.append("client_id", selectedClientId);
    formData.append("message", text);

    // add file(s) to request
    for (const file of files) {
        formData.append("media[]", file);
    }

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function () {
            messageInput.value = "";
            fileInput.value = "";
            previewArea.innerHTML = "";

            reloadMessagesSilent(selectedClientId);
            scrollToBottom();
        }
    });
}


// -------------------------------------------------------------------
// Scroll bottom
// -------------------------------------------------------------------
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}


// -------------------------------------------------------------------
// Open media in modal
// -------------------------------------------------------------------
function openMedia(path) {
    const modal = document.getElementById("mediaModal");
    const content = document.getElementById("mediaModalContent");

    modal.classList.add("show");
    content.src = path;

    document.getElementById("closeMediaModal").onclick = () => {
        modal.classList.remove("show");
    };
}


// -------------------------------------------------------------------
// Toggle client info panel
// -------------------------------------------------------------------
function toggleClientInfo() {
    clientInfoPanel.classList.toggle("show");
}
