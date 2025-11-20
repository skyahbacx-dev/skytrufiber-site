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

    if (isLocked) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
    }

    loadClientInfo();
    loadMessages(true);
}

/******** LOAD CLIENT INFO ********/
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/******** LOAD CHAT MESSAGES ********/
function loadMessages(initialLoad = false) {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";
            let avatarPath = "upload/default-avatar.png";

            html += `
            <div class="msg ${side}">
                ${side === "client" ? `<img src="${avatarPath}" class="bubble-avatar">` : ""}
                <div>
                    <div class="bubble">${m.message || ""}</div>
                    <div class="meta">${m.created_at}</div>
                </div>
                ${side === "csr" ? `<img src="${avatarPath}" class="bubble-avatar">` : ""}
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
        data: fd,
        processData: false,
        contentType: false,
        success: function () {
            $("#messageInput").val("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages(false);
        }
    });
});

/******** MODAL MEDIA VIEWER ********/
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** AUTO REFRESH ********/
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1500);

loadClients();

/******** TOGGLE CLIENT INFO ********/
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}
