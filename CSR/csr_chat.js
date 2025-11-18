let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];

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
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/******** SELECT CLIENT ********/
function selectClient(id, name, assigned) {

    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    let isAssignedToMe = (assigned === csrUser); //⭐ username check, not fullname

    $("#messageInput").prop("disabled", !isAssignedToMe);
    $("#sendBtn").prop("disabled", !isAssignedToMe);

    if (!isAssignedToMe) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
    }

    loadClientInfo();
    loadMessages();
}

/******** ASSIGN / UNASSIGN CLIENT ********/
function assignClient(id){
    $.post("assign_client.php", { client_id:id }, function(){
        loadClients();
    });
}

function unassignClient(id){
    if(!confirm("Remove this client from your assignment?")) return;
    $.post("unassign_client.php", { client_id:id }, function(){
        loadClients();
    });
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

/******** LOAD CHAT ********/
function loadMessages(){
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `
            <div class="msg ${side}">
                <div class="bubble">${m.message||""}`;

            if (m.file_url) {
                if (m.media_type === "image") {
                    html += `<img src="${m.file_url}" class="file-img" onclick="openMedia('${m.file_url}')">`;
                } else {
                    html += `<video controls class="file-img" onclick="openMedia('${m.file_url}')">
                        <source src="${m.file_url}">
                    </video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);
    });
}

/******** PREVIEW MULTIPLE UPLOADS ********/
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
    fd.append("csr_user", csrUser);

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

/******** MEDIA MODAL ********/
function openMedia(src){
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** AUTO REFRESH ********/
setInterval(loadClients, 3000);
setInterval(loadMessages, 1500);
loadClients();
