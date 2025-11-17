let selectedClient = 0;
let filesToSend = [];

/* SIDEBAR */
function toggleSidebar(){
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* SLIDING CLIENT INFO */
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/* LOAD CLIENT LIST */
function loadClients(){
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* SELECT CLIENT */
function selectClient(id, name){
    selectedClient=id;
    $("#chatName").text(name);
    $("#messageInput").prop("disabled",false);
    $("#sendBtn").prop("disabled",false);

    loadClientInfo();
    loadMessages();
}

/* CLIENT INFO */
function loadClientInfo(){
    $.getJSON("client_info.php?id="+selectedClient, info=>{
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* LOAD CHAT */
function loadMessages(){
    if(!selectedClient) return;
    $.getJSON("load_chat_csr.php?client_id="+selectedClient, messages=>{
        let html="";
        messages.forEach(m=>{
            let side=(m.sender_type==="csr")?"csr":"client";
            html+=`
             <div class="msg ${side}">
               <div class="bubble">${m.message||""}
                 ${m.media_path?(
                    m.media_type==="image"?
                    `<br><img src="${m.media_path}" class="file-img">`:
                    `<br><video controls class="file-img"><source src="${m.media_path}"></video>`
                 ):""}
                 <div class="meta">${m.created_at}</div>
               </div>
             </div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* FILE PREVIEW */
$("#fileInput").on("change", function(e){
    filesToSend=[...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file=>{
        let reader=new FileReader();
        reader.onload=function(ev){
            $("#previewArea").append(`
            <div class="preview-item">
              ${file.type.includes("video")?
               `<video src="${ev.target.result}" muted autoplay loop></video>`:
               `<img src="${ev.target.result}">`}
            </div>`);
        };
        reader.readAsDataURL(file);
    });
});

/* SEND */
$("#sendBtn").click(function(){
    let msg=$("#messageInput").val();
    if(!msg && filesToSend.length===0) return;

    let fd=new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => fd.append("files[]", f));

    $.ajax({
        url:"save_chat_csr.php",
        method:"POST",
        data:fd,
        contentType:false,
        processData:false,
        success:function(){
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend=[];
            loadMessages();
        }
    });
});

/* AUTO REFRESH */
setInterval(loadMessages,1800);
loadClients();
