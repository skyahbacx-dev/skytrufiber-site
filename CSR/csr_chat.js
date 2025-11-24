// =======================================================
// CSR CHAT — FULL STABLE BUILD
// =======================================================

let selectedClient = 0;
let selectedClientAssigned = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;
let currentAssignClient = null;
let currentUnassignClient = null;

// =======================================
// SIDEBAR TOGGLE
// =======================================
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

// =======================================
// LOAD CLIENT LIST
// =======================================
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

// =======================================
// SELECT CLIENT
// =======================================
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

// =======================================
// LOAD CLIENT INFO (RIGHT PANEL)
// =======================================
function loadClientInfo() {
    if (!selectedClient) return;

    $.getJSON("client_info.php?id=" + selectedClient, (data) => {
        $("#infoName").text(data.name || "");
        $("#infoEmail").text(data.email || "");
        $("#infoDistrict").text(data.district || "");
        $("#infoBrgy").text(data.barangay || "");
    });
}

// =======================================
// LOAD CHAT MESSAGES
// =======================================
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

            newMsgs.forEach((m) => {
                const side = (m.sender_type === "csr") ? "csr" : "client";
                let attachment = "";

                if (m.media_path) {
                    if (m.media_type === "image") {
                        attachment = `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else if (m.media_type === "video") {
                        attachment = `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                    }
                }

                const html = `
                    <div class="msg-row ${side} animate-msg">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${attachment}</div>
                            <div class="meta">${m.created_at}</div>
                        </div>
                    </div>`;

                $("#chatMessages").append(html);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

// =======================================
// SEND MESSAGE
// =======================================
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress((e) => {
    if (e.key === "Enter") sendMessage();
});

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
        processData: false,
        contentType: false,
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

// =======================================
// FILE PREVIEW
// =======================================
$(".upload-icon").click(() => $("#fileInput").click());

$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach((file) => {
        let reader = new FileReader();
        reader.onload = (ev) => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

// =======================================
// INFO PANEL SLIDE
// =======================================
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

// =======================================
// MEDIA FULLSCREEN VIEWER
// =======================================
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// =======================================
// AUTO REFRESH
// =======================================
setInterval(() => loadClients($("#searchInput").val()), 2500);
setInterval(() => loadMessages(false), 1200);

// INITIAL LOAD
loadClients();
