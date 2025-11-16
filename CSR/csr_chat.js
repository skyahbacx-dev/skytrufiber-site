let selectedClient = 0;
function selectClient(id, name) {
    selectedClient = id;
    $("#chatName").text(name);

    loadMessages();
    loadClientInfo(id);

    $("#messageInput").prop("disabled", false);
}

let filesToSend = [];

/* SIDEBAR TOGGLE */
function toggleSidebar() {
    const sb = document.querySelector(".sidebar");
    const overlay = document.querySelector(".sidebar-overlay");
    
    sb.classList.toggle("open");
    overlay.classList.toggle("show");

    document.body.style.overflow = overlay.classList.contains("show") ? "hidden" : "auto";
}

/* CLIENT INFO PANEL */
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/* LOAD CLIENT LIST */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);

        // ENABLE CLIENT CLICK SELECTION
        $(".client-item").off("click").on("click", function () {
            selectedClient = $(this).data("id");
            loadClientInfo(selectedClient);
            loadMessages();
        });
    });
}

/* LOAD CLIENT INFO RIGHT SLIDE PANEL */
function loadClientInfo(id) {
    $.getJSON("client_info.php?id=" + id, res => {

        $("#clientInfoName").text(res.name);
        $("#clientInfoEmail").text(res.email);
        $("#clientInfoDistrict").text(res.district);
        $("#clientInfoBarangay").text(res.barangay);
        $("#clientInfoAssigned").text(res.assigned);

        $("#clientInfoPanel").addClass("show");  // slide out animation
    });
}

/* LOAD MESSAGES */
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";
            html += `<div class="msg ${side}"><div class="bubble">${m.message || ""}</div>`;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img" style="max-width:250px;border-radius:12px;">`;
                } else {
                    html += `<video controls class="file-video" style="max-width:250px;border-radius:12px;">
                                <source src="${m.media_path}">
                            </video>`;
                }
            }
            html += `<div class="meta">${m.created_at}</div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* PREVIEW SELECTED FILES */
$("#fileInput").on("change", function(e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = e => {
            $("#previewArea").append(`
                <div class="preview-item">
                    ${file.type.includes("video") ?
                        `<video src="${e.target.result}" muted></video>` :
                        `<img src="${e.target.result}">`
                    }
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* SEND MESSAGE & FILES */
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
        contentType: false,
        processData: false,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/* ONLINE STATUS */
function checkStatus() {
    if (!selectedClient) return;

    $.get("check_status.php?id=" + selectedClient, res => {
        $("#statusDot")
            .toggleClass("online", res.status === "online")
            .toggleClass("offline", res.status !== "online");
    });
}

setInterval(checkStatus, 3000);
setInterval(loadMessages, 2000);
loadClients();
