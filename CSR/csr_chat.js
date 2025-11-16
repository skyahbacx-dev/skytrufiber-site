let selectedClient = 0;
let filesToSend = [];

/* Toggle sidebar */
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* Toggle client info panel */
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/* Load Client List */
function loadClients(){
    $.get("client_list.php", res=>{
        $("#clientList").html(res);
    });
}

/* Select Client */
function selectClient(id, name){
    selectedClient = id;
    $("#chatName").text(name);
    $("#messageInput").prop("disabled", false);
    $("#sendBtn").prop("disabled", false);

    loadClientInfo();
    loadMessages();
}

/* Client Info Slide Panel */
function loadClientInfo(){
    $.getJSON("client_info.php?id=" + selectedClient, data=>{
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
        $("#infoPhone").text(data.phone);
        $("#infoAssigned").text(data.assigned_csr);
    });
}

/* Load Messages */
function loadMessages(){
    if(!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages=>{
        let html = "";
        messages.forEach(m=>{
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `
            <div class="msg ${side}">
              <div class="bubble">${m.message || ""}`;

            if (m.media_path){
                if(m.media_type==="image"){
                    html += `<img src="${m.media_path}" class="file-img">`;
                } else {
                    html += `<video controls class="file-video"><source src="${m.media_path}"></video>`;
                }
            }

            html += `</div><div class="meta">${m.created_at}</div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* File Preview */
$("#fileInput").change(e=>{
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(f=>{
        let reader = new FileReader();
        reader.onload = e=>{
            $("#previewArea").append(`
                <div class="preview-item">
                    ${f.type.includes("video")
                        ? `<video muted src="${e.target.result}"></video>`
                        : `<img src="${e.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(f);
    });
});

/* Send Message */
$("#sendBtn").click(function(){
    let msg = $("#messageInput").val();
    if(!msg && filesToSend.length===0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(f=>fd.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: fd,
        processData:false,
        contentType:false,
        success:()=>{
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/* Auto reload */
setInterval(loadMessages, 1500);
loadClients();
