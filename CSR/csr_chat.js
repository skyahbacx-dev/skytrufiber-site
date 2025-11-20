let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];

/***** SIDEBAR *****/
function toggleSidebar(){
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/***** CLIENT INFO SLIDER *****/
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/***** LOAD CLIENT LIST + SEARCH *****/
function loadClients(){
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

$(".search").on("keyup", function(){
    let txt = $(this).val();
    $.get("client_list.php?search=" + txt, data => {
        $("#clientList").html(data);
    });
});

/***** SELECT CLIENT *****/
function selectClient(id, name, assigned){
    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $("#client-"+id).addClass("active-client");

    $("#chatName").text(name);

    let editable = (!assigned || assigned === csrFullname);
    $("#messageInput").prop("disabled", !editable);
    $("#sendBtn").prop("disabled", !editable);

    if (!editable) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
    }

    loadClientInfo();
    loadMessages();
}

/***** ASSIGN / UNASSIGN *****/
function assignClient(id){
    $.post("assign_client.php", {client_id:id}, () => loadClients());
}
function unassignClient(id){
    if (!confirm("Remove this client?")) return;
    $.post("unassign_client.php", {client_id:id}, () => loadClients());
}

/***** LOAD CLIENT INFO *****/
function loadClientInfo(){
    $.getJSON("client_info.php?id="+selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/***** LOAD MESSAGES *****/
function loadMessages(){
    if (!selectedClient) return;
    $.getJSON("load_chat_csr.php?client_id="+selectedClient, messages => {

        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html+=`
            <div class="msg ${side}">
                <div class="bubble">${m.message || ""}`;
            
            if (m.media_path){
                if (m.media_type === "image"){
                    html += `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                } else {
                    html += `<video controls class="file-img" onclick="openMedia('${m.media_path}')">
                                <source src="${m.media_path}">
                            </video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);
    });
}

/***** PREVIEW FILES *****/
$("#fileInput").change(function(e){
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file=>{
        let reader=new FileReader();
        reader.onload=ev=>{
            $("#previewArea").append(`
                <div class="preview-thumb">
                ${file.type.includes("video")?
                    `<video src="${ev.target.result}" muted></video>`:
                    `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/***** SEND MESSAGE *****/
$("#sendBtn").click(function(){
    let msg=$("#messageInput").val();
    if (!msg && filesToSend.length===0) return;

    let fd=new FormData();
    fd.append("message",msg);
    fd.append("client_id",selectedClient);
    fd.append("csr_fullname",csrFullname);

    filesToSend.forEach(f=>fd.append("files[]",f));

    $.ajax({
        url:"save_chat_csr.php",
        method:"POST",
        data:fd,
        processData:false,
        contentType:false,
        success:function(){
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend=[];
            loadMessages();
        }
    });
});

/***** FULLSCREEN MEDIA VIEW *****/
function openMedia(src){
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(()=>$("#mediaModal").removeClass("show"));

/***** AUTO REFRESH *****/
setInterval(loadMessages, 2000);
setInterval(loadClients, 4000);

loadClients();
