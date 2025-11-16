let selectedClient = 0;
function selectClient(id, name) {
    selectedClient = id;

    // Update UI header
    $("#clientName").text(name);

    // Show client info panel with slide animation
    $("#clientInfoPanel").addClass("show");

    loadMessages();
    loadClientInfo();
}

let filesToSend = [];

/* Load client list */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* Load messages */
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `
            <div class="msg ${side}">
                <div class="bubble">
                    <small style="font-size:12px;opacity:.6">${side.toUpperCase()}</small><br>
                    ${m.message || ""}
                </div>
                <div class="meta">${m.created_at}</div>
            </div>
            `;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}
/* Load Client Info */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBarangay").text(info.barangay);
        $("#infoPhone").text(info.phone);
    });
}

/* File Preview */
$("#fileInput").on("change", function(e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = function(e) {
            $("#previewArea").append(`
                <div class="preview-item">
                    ${
                        file.type.includes("video")
                        ? `<video src="${e.target.result}" muted></video>`
                        : `<img src="${e.target.result}">`
                    }
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* SEND MESSAGE */
$("#sendBtn").click(function(){
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
        success: function() {
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages();
        }
    });
});
function checkStatus() {
    if (!selectedClient) return;

    $.get("check_status.php?id=" + selectedClient, res => {
        if(res === "online") {
            $("#statusDot").removeClass("offline").addClass("online");
        } else {
            $("#statusDot").removeClass("online").addClass("offline");
        }
    });
}

setInterval(checkStatus, 3000);
function closeClientInfo(){
    $("#clientInfoPanel").removeClass("show");
}
