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

/******** SELECT CLIENT (called from client_list.php onclick) ********/
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo     = assigned || "";

    // highlight
    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    // username-based permissions (CSR1, CSR2, etc.)
    const isMine = (assignedTo === "" || assignedTo === csrUser);

    $("#messageInput").prop("disabled", !isMine);
    $("#sendBtn").prop("disabled", !isMine);

    if (!isMine) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
        $("#chatStatus").html(`<span id="statusDot" class="status-dot offline"></span> Locked (handled by ${assignedTo})`);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
        $("#chatStatus").html(`<span id="statusDot" class="status-dot online"></span> Active Chat`);
    }

    loadClientInfo();
    loadMessages();
}

/******** ASSIGN / UNASSIGN ********/
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

/******** LOAD CLIENTS ********/
function loadClients(search=""){
    $.get("client_list.php", { search: search }, data => {
        $("#clientList").html(data);
    });
}

/******** LOAD CLIENT INFO ********/
function loadClientInfo(){
    if (!selectedClient) return;
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name || "");
        $("#infoEmail").text(info.email || "");
        $("#infoDistrict").text(info.district || "");
        $("#infoBrgy").text(info.barangay || "");
    });
}

/******** LOAD MESSAGES ********/
function loadMessages(){
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";

        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">
                            ${m.message ? m.message.replace(/\n/g,"<br>") : ""}`;

            // show media from chat.media_path
            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<br><img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                } else if (m.media_type === "video") {
                    html += `<br><video controls class="file-img" onclick="openMedia('${m.media_path}')">
                                <source src="${m.media_path}">
                             </video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div>
                     </div>
                    </div>`;
        });

        $("#chatMessages").html(html);
        // no auto-scroll as you requested
    });
}

/******** PREVIEW MULTIPLE FILES (floating strip) ********/
function setupFilePreview(){
    $("#fileInput").on("change", function(e){
        filesToSend = [...e.target.files];
        $("#previewArea").html("");

        filesToSend.forEach(file => {
            let reader = new FileReader();
            reader.onload = ev => {
                const isVideo = file.type.includes("video");
                $("#previewArea").append(`
                    <div class="preview-item">
                        ${isVideo
                            ? `<video src="${ev.target.result}" muted></video>`
                            : `<img src="${ev.target.result}">`}
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        });
    });
}

/******** SEND MESSAGE ********/
function setupSend(){
    $("#sendBtn").click(function(){
        let msg = $("#messageInput").val();
        if (!msg && filesToSend.length === 0) return;
        if (!selectedClient) return;

        let fd = new FormData();
        fd.append("message", msg);
        fd.append("client_id", selectedClient);
        fd.append("csr_user", csrUser);

        filesToSend.forEach(f => fd.append("files[]", f));

        $.ajax({
            url: "save_chat_csr.php",
            method: "POST",
            data: fd,
            processData: false,
            contentType: false,
            success: function(res){
                $("#messageInput").val("");
                $("#previewArea").html("");
                $("#fileInput").val("");
                filesToSend = [];
                loadMessages();
            }
        });
    });

    // ENTER key send
    $("#messageInput").on("keydown", function(e){
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            $("#sendBtn").click();
        }
    });
}

/******** MEDIA MODAL ********/
function openMedia(src){
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$(document).on("click", "#closeMediaModal", () => {
    $("#mediaModal").removeClass("show");
});

/******** INIT AFTER DOM LOADED ********/
$(function(){

    loadClients();

    // Search filter
    $(".search").on("keyup", function(){
        const q = $(this).val();
        loadClients(q);
    });

    setupFilePreview();
    setupSend();

    // background reload
    setInterval(function(){
        // refresh only list; do not reset selection
        const q = $(".search").val() || "";
        loadClients(q);
        loadMessages();
    }, 3000);
});
