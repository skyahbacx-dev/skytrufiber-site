let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;

/******** LOAD CLIENT LIST ********/
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/******** SELECT CLIENT ********/
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");
    $("#chatName").text(name);

    const isLocked = assigned && assigned !== csrFullname;

    $("#messageInput").prop("disabled", isLocked);
    $("#sendBtn").prop("disabled", isLocked);

    loadClientInfo();
    loadMessages(true);
}

/******** CLIENT INFO ********/
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/******** LOAD CHAT ********/
function loadMessages(initialLoad = false) {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";

        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";
            html += `
            <div class="msg ${side}">
                <div class="bubble">${m.message || ""}</div>
                <div class="meta">${m.created_at}</div>
            </div>`;
        });

        $("#chatMessages").html(html);

        if (messages.length > lastMessageCount && !initialLoad) {
            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/******** SEND MESSAGE ********/
$("#sendBtn").click(function () {
    let msg = $("#messageInput").val();
    if (!msg) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function () {
            $("#messageInput").val("");
            loadMessages(false);
        }
    });
});

/******** AUTO REFRESH ********/
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1500);

loadClients();
