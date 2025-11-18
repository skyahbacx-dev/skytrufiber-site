/*********************************************************
 STRICT RULE: NO DESIGN CHANGE - ONLY ADD/FIX BEHAVIOR
*********************************************************/

let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;

/******** SIDEBAR ********/
function toggleSidebar(){
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/******** SLIDING CLIENT INFO PANEL ********/
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/******** LOAD CLIENT LIST ********/
function loadClients(search = ""){
    $.get("client_list.php?search=" + search, data => {
        $("#clientList").html(data);
    });
}

$(".search").on("keyup", function(){
    loadClients($(this).val());
});


/******** SELECT CLIENT ********/
function selectClient(id, name, assigned){
    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    // enable or disable input based on assignment status
    let canSend = (!assigned || assigned === csrFullname);
    $("#messageInput").prop("disabled", !canSend);
    $("#sendBtn").prop("disabled", !canSend);
    $("#fileInput").prop("disabled", !canSend);

    if (!canSend) $(".upload-icon").hide();
    else $(".upload-icon").show();

    loadClientInfo();
    loadMessages();
}


/******** ASSIGN + UNASSIGN ********/
function assignClient(id){
    $.post("assign_client.php", {client_id:id}, ()=> loadClients());
}

function unassignClient(id){
    if(!confirm("Remove from your client list?")) return;
    $.post("unassign_client.php", {client_id:id}, ()=> loadClients());
}


/******** LOAD CLIENT INFO ********/
function loadClientInfo(){
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}


/******** LOAD MESSAGES ********/
function loadMessages(){
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (messages.length === lastMessageCount) return;
        lastMessageCount = messages.length;

        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                       <div class="bubble">${m.message || ""}`;

            // media
            if (m.media_path){
                if (m.media_type === "image"){
                    html += `<img src="${m.media_path}" 
                                   class="file-img" 
                                   onclick="openMedia('${m.media_path}')">`;
                } else {
                    html += `<video class="file-img" controls onclick="openMedia('${m.media_path}')">
                                <source src="${m.media_path}">
                             </video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}


/******** PREVIEW MULTIPLE FILES ********/
$("#fileInput").on("change", function(e){
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                      ? `<video src="${ev.target.result}" muted></video>`
                      : `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});


/******** SEND MESSAGE ********/
$("#sendBtn").click(function(){
    let msg = $("#messageInput").val();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => fd.append("files[]", f));

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
            filesToSend = [];
            loadMessages();
        }
    });
});


/******** MODAL VIEW MEDIA FULLSCREEN ********/
function openMedia(src){
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));


/******** AUTO REFRESH ********/
setInterval(()=>loadClients($(".search").val()), 5000);
setInterval(loadMessages, 1500);

loadClients();
