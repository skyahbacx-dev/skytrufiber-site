// =======================================================
// CSR CHAT — FULL JAVASCRIPT FINAL BUILD
// =======================================================

let selectedClient = 0;
let selectedClientAssigned = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;
let currentAssignClient = null;
let currentUnassignClient = null;

/* SIDEBAR */
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* LOAD CLIENTS */
function loadClients(search = "") {
    $.get("client_list.php", { search }, data => $("#clientList").html(data));
}

/* SELECT CLIENT */
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

/* LOAD CLIENT INFO */
function loadClientInfo() {
    if (!selectedClient) return;

    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name || "");
        $("#infoEmail").text(info.email || "");
        $("#infoDistrict").text(info.district || "");
        $("#infoBrgy").text(info.barangay || "");
    });
}

/* LOAD CHAT MESSAGES */
function loadMessages(initial = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            messages.slice(lastMessageCount).forEach(m => {
                const side = m.sender_type === "csr" ? "outgoing" : "incoming";

                let attachment = "";
                if (m.media_url) {
                    attachment = m.media_type === "image"
                        ? `<img src="${m.media_url}" class="chat-image" onclick="openMedia('${m.media_url}')">`
                        : `<video class="chat-video" controls><source src="${m.media_url}"></video>`;
                }

                $("#chatMessages").append(`
                    <div class="message-row ${side}">
                        <div class="message-bubble">
                            ${m.message || ""}
                            ${attachment}
                            <div class="message-time">${m.created_at}</div>
                        </div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/* SEND MESSAGE */
$("#sendBtn").click(sendMessage);
$("#messageInput").keydown(e => { if (e.key === "Enter") sendMessage(); });

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
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages(false);
            loadClients();
        }
    });
}

/* PREVIEW MEDIA */
$(".upload-icon").on("click", () => $("#fileInput").click());

$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-box">
                    <img src="${ev.target.result}" class="preview-media">
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* CLIENT INFO PANEL */
function toggleClientInfo() {
    $("#clientInfoPanel").toggleClass("show");
}

/* AUTO REFRESH */
setInterval(() => loadClients($("#searchInput").val()), 2500);
setInterval(() => loadMessages(false), 1200);

loadClients();
