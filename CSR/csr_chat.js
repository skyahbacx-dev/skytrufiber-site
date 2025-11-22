// ===============================================
// CSR CHAT — FULL JAVASCRIPT
// ===============================================

let selectedClient = 0;
let selectedClientAssigned = "";
let filesToSend = [];
let currentAssignClient = null;
let currentUnassignClient = null;
let lastMessageCount = 0;

// =========================
// SIDEBAR
// =========================
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

// =========================
// LOAD CLIENT LIST
// =========================
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

// =========================
// SELECT A CLIENT
// =========================
function selectClient(id, name, assignedTo) {
    selectedClient = id;
    selectedClientAssigned = assignedTo || "";

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    const locked = assignedTo && assignedTo !== csrUser;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    $(".upload-icon").toggle(!locked);

    $("#chatMessages").html("");

    loadMessages(true);
    loadClientInfo();
}

// =========================
// LOAD CLIENT INFO PANEL
// =========================
function loadClientInfo() {
    if (!selectedClient) return;

    $.getJSON("client_info.php?id=" + selectedClient, data => {
        $("#infoName").text(data.name || "");
        $("#infoEmail").text(data.email || "");
        $("#infoDistrict").text(data.district || "");
        $("#infoBrgy").text(data.barangay || "");
    });
}

// =========================
// LOAD CHAT MESSAGES
// =========================
function loadMessages(initial = false) {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = m.sender_type === "csr" ? "csr" : "client";

                let attachment = "";
                if (m.media_url) {
                    attachment = `<img src="${m.media_url}" class="file-img" onclick="openMedia('${m.media_url}')">`;
                }

                const html = `
                <div class="msg-row ${side}">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${attachment}</div>
                        <div class="meta">${m.created_at}</div>
                    </div>
                </div>
                `;

                $("#chatMessages").append(html);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

// =========================
// SEND MESSAGE
// =========================
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => fd.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        contentType: false,
        processData: false,
        data: fd,
        success: function () {
            $("#messageInput").val("");

            $("#previewArea").fadeOut(150, function () {
                $("#previewArea").html("").show();
            });

            $("#fileInput").val("");
            filesToSend = [];

            loadMessages(false);
            loadClients();
        }
    });
}

// =========================
// FILE PREVIEW
// =========================
$(".upload-icon").on("click", () => $("#fileInput").click());

$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    <img src="${ev.target.result}">
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });

    $("#previewArea").show();
});

// =========================
// POPUP — ASSIGN / UNASSIGN
// =========================
function showAssignPopup(id) {
    currentAssignClient = id;
    $("#assignPopup").fadeIn(150);
}
function closeAssignPopup() { $("#assignPopup").fadeOut(150); }

function confirmAssign() {
    $.post("assign_client.php", { client_id: currentAssignClient }, () => {
        closeAssignPopup();
        loadClients();
        loadMessages(true);
    });
}

function showUnassignPopup(id) {
    currentUnassignClient = id;
    $("#unassignPopup").fadeIn(150);
}
function closeUnassignPopup() { $("#unassignPopup").fadeOut(150); }

function confirmUnassign() {
    $.post("unassign_client.php", { client_id: currentUnassignClient }, () => {
        closeUnassignPopup();
        loadClients();
        loadMessages(true);
        $("#chatPanel").html(`<p class="placeholder">👈 Select a client to chat</p>`);
        selectedClient = 0;
    });
}

// =========================
// MEDIA VIEWER
// =========================
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// =========================
// INFO PANEL TOGGLE
// =========================
$(document).on("click", ".info-btn", function () {
    $("#clientInfoPanel").toggleClass("show");
});

// =========================
// AUTO REFRESH
// =========================
setInterval(() => loadClients($("#searchInput").val()), 3000);
setInterval(() => loadMessages(false), 1200);

// =========================
// INITIAL LOAD
// =========================
loadClients();
