let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;

/******** SIDEBAR ********/
function toggleSidebar(){
    document.querySelector(".sidebar").classList.toggle("show");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
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

    const isLocked = assigned && assigned !== csrFullname;
    $("#messageInput, #sendBtn, #fileInput").prop("disabled", isLocked);

    if (window.innerWidth < 900) {
        document.querySelector(".sidebar").classList.remove("show");
        document.querySelector(".sidebar-overlay").classList.remove("show");
    }

    loadClientInfo();
    loadMessages(false);
}

/******** LOAD CLIENT INFO ********/
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/******** LOAD MESSAGES ********/
function loadMessages(scrollBottom){
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                <div class="bubble">${m.message || ""}`;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                } else {
                    html += `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);

        if (scrollBottom || messages.length > lastMessageCount) {
            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

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
            loadMessages(true);
        }
    });
});

/******** FILE PREVIEW ********/
$(".upload-icon").click(() => $("#fileInput").click());

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
                </div>`);
        };
        reader.readAsDataURL(file);
    });
});

/******** VIEW MEDIA ********/
function openMedia(src){
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** REFRESH AUTO ********/
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1500);

loadClients();

/******** Info Panel Toggle ********/
function toggleClientInfo(){
    document.querySelector("#clientInfoPanel").classList.toggle("show");
}
