let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];

function toggleSidebar(){
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

function selectClient(id, name, assigned){
    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#messageInput").prop("disabled", assigned && assigned !== csrFullname);
    $("#sendBtn").prop("disabled", assigned && assigned !== csrFullname);

    loadClientInfo();
    loadMessages();
}

function loadMessages(){
    if(!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, msgs => {
        let html = "";
        msgs.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">${m.message || ""}`;

            if (m.media && m.media.length > 0) {
                m.media.forEach(media => {
                    if(media.media_type === "image"){
                        html += `<img src="${media.media_path}" class="file-img" onclick="openMedia('${media.media_path}')">`;
                    } else {
                        html += `<video controls class="file-img" onclick="openMedia('${media.media_path}')">
                                    <source src="${media.media_path}">
                                 </video>`;
                    }
                });
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

$("#fileInput").on("change", function(e){
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file =>{
        let reader = new FileReader();
        reader.onload = ev =>{
            $("#previewArea").append(`
                <div class="preview-item">
                    ${file.type.includes("video") ?
                        `<video src="${ev.target.result}" muted></video>` :
                        `<img src="${ev.target.result}">`
                    }
                </div>
            `);
        }
        reader.readAsDataURL(file);
    });
});

$("#sendBtn").click(() =>{
    let msg = $("#messageInput").val();
    if(!msg && filesToSend.length===0) return;

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
        success:()=>{
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});

function openMedia(src){
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src",src);
}

$("#closeMediaModal").click(()=> $("#mediaModal").removeClass("show"));

setInterval(loadClients,4000);
setInterval(loadMessages,1500);

loadClients();
