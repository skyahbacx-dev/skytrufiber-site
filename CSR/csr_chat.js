let selectedClient = 0;
let filesToSend = [];

const csrFullname = "<?= $csr_fullname ?>";

/* Sidebar */
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("collapsed");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* Load clients */
function loadClients(){
    $.get("client_list.php", data => $("#clientList").html(data));
}

/* Select client */
function selectClient(id, name){
    selectedClient = id;
    $("#chatName").text(name);
    $("#messageInput, #sendBtn").prop("disabled", false);
    loadClientInfo(id);
    loadMessages();
}

/* Load client info */
function loadClientInfo(id){
    $.getJSON("client_info.php?id=" + id, res=>{
        $("#infoName").text(res.name);
        $("#infoEmail").text(res.email);
        $("#infoDistrict").text(res.district);
        $("#infoBarangay").text(res.barangay);
        $("#infoPhone").text(res.phone);
    });
}

/* Toggle info sliding panel */
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/* Load messages */
function loadMessages(){
    if (!selectedClient) return;
    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, msgs=>{
        let html="";
        msgs.forEach(m=>{
            let side = m.sender_type === "csr" ? "csr" : "client";
            html += `<div class="msg ${side}">
                        <div class="bubble">${m.message || ""}</div>`;

            if (m.media_path){
                if (m.media_type === "image"){
                    html+=`<img src="${m.media_path}" class="file-img">`;
                } else {
                    html+=`<video controls class="file-video"><source src="${m.media_path}"></video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div>`;
        });
        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* Preview files */
$("#fileInput").change(function(e){
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file=>{
        let reader = new FileReader();
        reader.onload = r=>{
            $("#previewArea").append(`<div class="preview-item">
                ${file.type.includes("video")
                ? `<video src="${r.target.result}" muted></video>`
                : `<img src="${r.target.result}">`}
            </div>`);
        };
        reader.readAsDataURL(file);
    });
});

/* Send chat */
$("#sendBtn").click(function(){
    let txt = $("#messageInput").val();
    if(!txt && filesToSend.length===0)return;

    let fd = new FormData();
    fd.append("message", txt);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(file=>fd.append("files[]", file));

    $.ajax({
        url:"save_chat_csr.php",
        method:"POST",
        data:fd,
        processData:false,
        contentType:false,
        success:()=>{
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend=[];
            loadMessages();
        }
    });
});

setInterval(loadMessages, 1500);
loadClients();
