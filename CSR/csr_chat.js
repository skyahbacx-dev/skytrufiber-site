let selectedClient = 0;
let filesToSend = [];

/* Load clients list */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* Select client */
function selectClient(id, name) {
    selectedClient = id;
    $("#chatName").text(name);
    $("#messageInput").prop("disabled", false);
    $("#sendBtn").prop("disabled", false);

    loadClientInfo();
    loadMessages();
}

/* Load client info */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* Load Messages */
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = m.sender_type === "csr" ? "csr" : "client";

            html += `
            <div class="msg ${side}">
                <div class="bubble">${m.message || ""}`;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img bubble-img">`;
                } else {
                    html += `<video controls class="file-img bubble-img"><source src="${m.media_path}"></video>`;
                }
            }

            html += `</div><div class="meta">${m.created_at}</div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* Preview Multiple Images & Videos */
$("#fileInput").on("change", function(e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach((file, idx) => {
        let reader = new FileReader();
        reader.onload = function(ev) {
            $("#previewArea").append(`
                <div class="preview-item fade-in">
                    <span class="remove-preview" onclick="removePreview(${idx})">✖</span>
                    ${
                        file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`
                    }
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* Remove Preview Item */
function removePreview(index) {
    filesToSend.splice(index, 1);
    $("#fileInput").val("");
    $("#previewArea").html("");
    filesToSend.forEach((f, i) => {
        let reader = new FileReader();
        reader.onload = function(ev) {
            $("#previewArea").append(`
                <div class="preview-item fade-in">
                    <span class="remove-preview" onclick="removePreview(${i})">✖</span>
                    ${
                        f.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`
                    }
                </div>
            `);
        };
        reader.readAsDataURL(f);
    });
}

/* Send message + media */
$("#sendBtn").click(function(){
    let msg = $("#messageInput").val();
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
        contentType: false,
        processData: false,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});
function assignClient(id){
    $.post("assign_client.php", { client_id:id }, function(){
        loadClients();
    });
}

function unassignClient(id){
    $.post("unassign_client.php", { client_id:id }, function(){
        loadClients();
    });
}

/* Refresh messages */
setInterval(loadMessages, 2000);
loadClients();
