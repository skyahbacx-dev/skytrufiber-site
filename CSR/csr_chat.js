let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;

/******** SIDEBAR ********/
function toggleSidebar(){
    document.querySelector(".sidebar")?.classList.toggle("show");
    document.querySelector(".sidebar-overlay")?.classList.toggle("show");
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

    const locked = assigned && assigned !== csrFullname;
    $("#messageInput, #sendBtn, #fileInput").prop("disabled", locked);
    if (locked) $(".upload-icon").hide(); else $(".upload-icon").show();

    // remove unread
    $(`#client-${id} .badge`).remove();
    $.post("mark_read.php", { client_id:id });

    loadClientInfo();
    loadMessages(true);
}

/******** CLIENT INFO ********/
function loadClientInfo() {
    if (!selectedClient) return;
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/******** LOAD MESSAGES (WITH ANIMATION) ********/
function loadMessages(initialLoad = false){
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        let html = "";
        let lastDate = "";

        messages.forEach(m => {

            let msgDate = new Date(m.created_at).toDateString();
            if (msgDate !== lastDate) {
                html += `<div class="date-separator">${msgDate}</div>`;
                lastDate = msgDate;
            }

            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">${m.message || ""}`;

            if (m.media_url) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_url}" class="file-img" onclick="openMedia('${m.media_url}')">`;
                } else {
                    html += `<video class="file-img" controls><source src="${m.media_url}"></video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div>`;
            html += `</div></div>`;
        });

        $("#chatMessages").html(html);

        // ANIMATION FOR NEW MESSAGE
        $("#chatMessages .msg").last().addClass("animated");

        if (messages.length > lastMessageCount || initialLoad) {
            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/******** FILE UPLOAD PREVIEW ********/
$(".upload-icon").on("click", () => $("#fileInput").click());

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

/******** SEND ********/
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
            loadMessages(false);
            loadClients();
        }
    });
});

/******** MEDIA VIEWER ********/
function openMedia(src){
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** AUTO REFRESH ********/
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1500);

/******** SLIDE PANEL ********/
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

loadClients();
