// =======================================================
// CSR CHAT — FULL JAVASCRIPT FINAL VERSION
// MULTI-MEDIA SUPPORT + B2 STORAGE + CLIENT INFO SLIDE
// =======================================================

let selectedClient = 0;
let selectedClientAssigned = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

let currentAssignClient = null;
let currentUnassignClient = null;

// =============================
// SIDEBAR
// =============================
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

// =============================
// LOAD CLIENT LIST
// =============================
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

// =============================
// SELECT A CLIENT
// =============================
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
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

// =============================
// LOAD CLIENT INFO
// =============================
function loadClientInfo() {
    if (!selectedClient) return;

    $.getJSON("client_info.php?id=" + selectedClient, (d) => {
        $("#infoName").text(d.name || "");
        $("#infoEmail").text(d.email || "");
        $("#infoDistrict").text(d.district || "");
        $("#infoBrgy").text(d.barangay || "");
    });
}

// =============================
// LOAD CHAT MESSAGES
// =============================
function loadMessages(initial = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, (messages) => {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = m.sender_type === "csr" ? "csr" : "client";

                let body = `${m.message ? m.message : ""}`;

                // MULTIPLE MEDIA
                if (m.media_files && m.media_files.length > 0) {
                    m.media_files.forEach(file => {
                        if (file.media_type === "image") {
                            body += `<img src="${file.media_path}" class="file-img" onclick="openMedia('${file.media_path}')">`;
                        } else if (file.media_type === "video") {
                            body += `<video class="file-img" controls><source src="${file.media_path}"></video>`;
                        }
                    });
                }

                const html = `
                <div class="msg-row ${side} animate-msg">
                    <img src="upload/default-avatar.png" class="msg-avatar">
                    <div class="bubble-wrapper">
                        <div class="bubble">${body}</div>
                        <div class="meta">${m.created_at}</div>
                    </div>
                </div>
                `;

                $("#chatMessages").append(html);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

// =============================
// SEND MESSAGE
// =============================
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
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
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];

            loadMessages(false);
            loadClients();
        }
    });
}

// =============================
// MEDIA PREVIEW BEFORE SENDING
// =============================
$(".upload-icon").on("click", () => $("#fileInput").click());

$("#fileInput").on("change", (e) => {
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
});

// =============================
// ASSIGN + UNASSIGN POPUPS
// =============================
function showAssignPopup(id) {
    currentAssignClient = id;
    $("#assignPopup").fadeIn(140);
}
function closeAssignPopup() {
    $("#assignPopup").fadeOut(140);
}
function confirmAssign() {
    $.post("assign_client.php", { client_id: currentAssignClient }, () => {
        closeAssignPopup();
        loadClients();
        loadMessages(true);
    });
}

function showUnassignPopup(id) {
    currentUnassignClient = id;
    $("#unassignPopup").fadeIn(140);
}
function closeUnassignPopup() {
    $("#unassignPopup").fadeOut(140);
}
function confirmUnassign() {
    $.post("unassign_client.php", { client_id: currentUnassignClient }, () => {
        closeUnassignPopup();
        loadClients();
        $("#chatMessages").html("<p class='placeholder'>👈 Select a client to chat</p>");
        selectedClient = 0;
    });
}

// =============================
// CLIENT INFO SLIDE PANEL
// =============================
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

// =============================
// MEDIA VIEWER
// =============================
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// =============================
// AUTO REFRESH
// =============================
setInterval(() => loadClients($("#searchInput").val()), 2500);
setInterval(() => loadMessages(false), 1200);

// Start
loadClients();
