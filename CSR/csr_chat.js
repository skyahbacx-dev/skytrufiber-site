let selectedClient = 0;
let filesToSend = [];

/* ========== SIDEBAR TOGGLE ========== */
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");

    // Disable background scroll
    if (document.querySelector(".sidebar-overlay").classList.contains("show")) {
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = "auto";
    }
}

/* ========== CLIENT INFO PANEL ========== */
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/* ========== LOAD CLIENT LIST ========== */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* ========== SELECT CLIENT & ENABLE CHAT ========== */
function selectClient(id, name) {
    selectedClient = id;
    $("#chatName").text(name);
    $("#chatStatus").text("Active Chat");

    document.getElementById("messageInput").disabled = false;
    document.getElementById("sendBtn").disabled = false;

    loadClientInfo();
    loadMessages();
}

/* ========== LOAD CLIENT INFO SLIDER ========== */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* ========== LOAD MESSAGES FROM SERVER ========== */
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `
            <div class="msg ${side}">
                <div class="bubble">
                    ${m.message || ""}
            `;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<br><img src="${m.media_path}" class="file-img">`;
                } else {
                    html += `
                    <br><video controls class="file-img">
                        <source src="${m.media_path}">
                    </video>`;
                }
            }

            html += `
                    <div class="meta">${m.created_at}</div>
                </div>
            </div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* ========== MULTIPLE FILE PREVIEW ========== */
$("#fileInput").on("change", function(e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
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
        processData: false,
        contentType: false,
        success: function() {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/* ========== ONLINE STATUS CHECKER ========== */
function checkStatus() {
    if (!selectedClient) return;

    $.getJSON("check_status.php?id=" + selectedClient, res => {
        $("#statusDot").removeClass("online offline").addClass(res.status);
    });
}

setInterval(loadMessages, 2000);
setInterval(checkStatus, 3000);
loadClients();
