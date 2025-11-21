let selectedClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* SIDEBAR TOGGLE */
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* LOAD CLIENT LIST */
function loadClients() {
    $.get("client_list.php", data => $("#clientList").html(data));
}

/* SELECT CLIENT */
function selectClient(id, name, assigned) {
    selectedClient = id;
    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatStatus").html(`<span class="status-dot offline"></span> Offline`);
    loadClientInfo();
    loadMessages(true);
}

/* LOAD CLIENT INFO */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* LOAD MESSAGES */
function loadMessages(initialLoad = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initialLoad) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            let newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = m.sender_type === "csr" ? "csr" : "client";
                const avatarImg = "upload/default-avatar.png";

                let attachment = "";
                if (m.media_url) {
                    attachment = `<img src="${m.media_url}" class="file-img">`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <img src="${avatarImg}" class="msg-avatar">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""} ${attachment}</div>
                            <div class="meta">${m.created_at}</div>
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
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: () => {
            $("#messageInput").val("");
            loadMessages(false);
        }
    });
}

/* AUTORELOAD */
setInterval(loadClients, 5000);
setInterval(() => loadMessages(false), 1200);

/* INFO PANEL */
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

loadClients();
