let selectedClient = 0;
let filesToSend = [];

/* ===== LOAD CLIENTS ===== */
function loadClients() {
    $.get("client_list.php", data => $("#clientList").html(data));
}

/* ===== SELECT CLIENT ===== */
function selectClient(id, name) {
    selectedClient = id;
    $("#chatName").text(name);
    $("#messageInput").prop("disabled", false);
    $("#sendBtn").prop("disabled", false);

    loadClientInfo();
    loadMessages();
}

/* ===== LOAD CLIENT INFO ===== */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* ===== LOAD MESSAGES ===== */
function loadMessages() {
    if (!selectedClient) return;
    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">
                            ${m.message || ""}`;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img">`;
                } else {
                    html += `<video controls class="file-img"><source src="${m.media_path}"></video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* ===== PREVIEW MEDIA ===== */
$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(f => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-item">
                    ${f.type.includes("video")
                    ? `<video src="${ev.target.result}" muted></video>`
                    : `<img src="${ev.target.result}">`}
                </div>`);
        };
        reader.readAsDataURL(f);
    });
});

/* ===== SEND MESSAGE ===== */
$("#sendBtn").click(() => {
    let msg = $("#messageInput").val();
    if (!msg && filesToSend.length === 0) return;

    let formData = new FormData();
    formData.append("message", msg);
    formData.append("client_id", selectedClient);
    formData.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => formData.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/* ===== ASSIGN & REMOVE CONTROLS ===== */
function assignClient(id) {
    $.post("assign_client.php", {client_id:id}, () => loadClients());
}

function removeClient(id) {
    $.post("remove_client.php", {client_id:id}, () => loadClients());
}

setInterval(loadMessages, 2000);
loadClients();
