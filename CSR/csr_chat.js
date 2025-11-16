let selectedClient = 0;
let filesToSend = [];

/* ========== SIDEBAR TOGGLE ========== */
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* ========== SLIDE CLIENT INFO ========== */
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/* ========== LOAD CLIENT LIST ========== */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* ========== SELECT CLIENT ========== */
function selectClient(id, name) {
    selectedClient = id;
    $("#chatName").text(name);
    $("#chatStatus").text("Active Chat");
    $("#messageInput").prop("disabled", false);
    $("#sendBtn").prop("disabled", false);

    loadClientInfo();
    loadMessages();
}

/* ========== LOAD CLIENT INFO PANEL ========== */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* ========== LOAD MESSAGES ========== */
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">
                            ${m.message || ""}`;

            // render media
            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img">`;
                } else {
                    html += `<video controls class="file-img"><source src="${m.media_path}"></video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div>
                        </div>
                    </div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* ========== PREVIEW UPLOAD MULTIPLE FILES ========== */
$("#fileInput").on("change", function(e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = function(ev) {
            $("#previewArea").append(`
                <div class="preview-item">
                    ${file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});


/* ========== SEND MESSAGE ========== */
$("#sendBtn").click(function(){
    let message = $("#messageInput").val();
    if (!message && filesToSend.length === 0) return;

    let formData = new FormData();
    formData.append("message", message);
    formData.append("client_id", selectedClient);
    formData.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => formData.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function() {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/* ========== STATUS CHECKER (ONLINE DOT) ========== */
function checkStatus() {
    if (!selectedClient) return;
    $.getJSON("check_status.php?id=" + selectedClient, res => {
        $("#statusDot").removeClass("online offline").addClass(res.status);
    });
}

setInterval(loadMessages, 2000);
setInterval(checkStatus, 3000);

loadClients();
