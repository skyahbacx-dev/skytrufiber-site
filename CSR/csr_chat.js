/* ===========================
   CSR CHAT SYSTEM JS (FINAL)
=========================== */

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

function loadClients(search = "") {
    $.get("client_list.php", { search }, function (data) {
        $("#clientList").html(data);
    });
}

function selectClient(id, name) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {

        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "me" : "them";

                let mediaHtml = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        mediaHtml = `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else {
                        mediaHtml = `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="message ${side}">
                        ${m.message || ""}${mediaHtml}
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

$("#sendBtn").on("click", sendMessage);
$("#messageInput").on("keypress", e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", activeClient);

    filesToSend.forEach(f => fd.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: () => {
            $("#messageInput").val("");
            filesToSend = [];
            $("#previewArea").html("");
            loadMessages(false);
            loadClients();
        }
    });
}

$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => $("#previewArea").append(`<img src="${ev.target.result}" class="preview-thumb">`);
        reader.readAsDataURL(file);
    });
});

function showAssignPopup(id) {
    $("#assignClientId").val(id);
    $("#assignModal").addClass("show");
}

function showUnassignPopup(id) {
    $("#unassignClientId").val(id);
    $("#unassignModal").addClass("show");
}

function assignClient() {
    $.post("assign_client.php", { client_id: $("#assignClientId").val() }, () => {
        $("#assignModal").removeClass("show");
        loadClients();
    });
}

function unassignClient() {
    $.post("unassign_client.php", { client_id: $("#unassignClientId").val() }, () => {
        $("#unassignModal").removeClass("show");
        loadClients();
    });
}

setInterval(loadMessages, 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);
loadClients();
