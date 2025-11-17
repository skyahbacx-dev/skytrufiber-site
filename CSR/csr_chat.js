let selectedClient = 0;
let filesToSend = [];

/* SIDEBAR */
function toggleSidebar(){
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* SLIDING PANEL */
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/* LOAD CLIENT LIST */
function loadClients(){
    $.get("client_list.php", d => $("#clientList").html(d));
}

/* SELECT CLIENT */
function selectClient(id, name){
    selectedClient = id;
    $("#chatName").text(name);
    $("#messageInput").prop("disabled", false);
    $("#sendBtn").prop("disabled", false);
    loadClientInfo();
    loadMessages();
}

/* LOAD CLIENT INFO */
function loadClientInfo(){
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* LOAD MESSAGES */
function loadMessages(){
    if (!selectedClient) return;
    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m =>{
            const side = (m.sender_type === "csr") ? "csr" : "client";

            html += `
            <div class="msg ${side}">
                <div class="bubble">${m.message || ""}`;

            if (m.media_path) {
                const path = "/CSR/upload/chat/" + m.media_path.split("/").pop();
                if (m.media_type === "image") {
                    html += `<img src="${path}" class="file-img">`;
                } else {
                    html += `<video controls class="file-img"><source src="${path}"></video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* PREVIEW MULTIPLE FILES */
$("#fileInput").on("change", e =>{
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file =>{
        let reader = new FileReader();
        reader.onload = ev =>{
            $("#previewArea").append(`
                <div class="preview-item">
                ${file.type.includes("video") ? `<video src="${ev.target.result}" muted></video>` : `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* SEND */
$("#sendBtn").click(()=>{
    let form = new FormData();
    form.append("message", $("#messageInput").val());
    form.append("client_id", selectedClient);
    form.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => form.append("files[]", f));

    $.ajax({
        url:"save_chat_csr.php",
        method:"POST",
        data:form,
        processData:false,
        contentType:false,
        success(){
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/* AUTO REFRESH */
setInterval(loadMessages,2000);
loadClients();
