let selectedClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/******** SIDEBAR ********/
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/******** LOAD CLIENTS ********/
function loadClients() {
    $.get("client_list.php", res => $("#clientList").html(res));
}

/******** SELECT CLIENT ********/
function selectClient(id, name, assigned) {
    selectedClient = id;

    $("#placeholderScreen").hide();
    $("#chatHeader").show();
    $("#chatMessages").show();
    $("#chatName").text(name);

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    loadClientInfo();
    loadMessages(true);
}

/******** CLIENT INFO ********/
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/******** LOAD MESSAGES ********/
function loadMessages(initial = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {

            messages.slice(lastMessageCount).forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";
                const avatarImg = "upload/default-avatar.png";

                let attachment = "";
                if (m.media_url) {
                    if (m.media_type === "image") attachment = `<img src="${m.media_url}" class="file-img">`;
                    if (m.media_type === "video") attachment = `<video class="file-img" controls><source src="${m.media_url}"></video>`;
                }

                const html = `
                    <div class="msg-row ${side} animate-msg">
                        <img src="${avatarImg}" class="msg-avatar">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message ?? ""} ${attachment}</div>
                            <div class="meta">${m.created_at}</div>
                        </div>
                    </div>
                `;
                $("#chatMessages").append(html);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/******** SEND MESSAGE ********/
$(".upload-icon").click(() => $("#fileInput").click());

$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video") ? `<video src="${e.target.result}" muted></video>` : `<img src="${e.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

$("#sendBtn").click(sendMessage);

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length===0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);
    filesToSend.forEach(f => fd.append("files[]", f));

    $.ajax({
        url:"save_chat_csr.php",
        type:"POST",
        data:fd,
        processData:false,
        contentType:false,
        success:()=> {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend=[];
            loadMessages(false);
        }
    });
}

/******** PANEL TOGGLE ********/
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/******** AUTO REFRESH ********/
setInterval(loadClients, 5000);
setInterval(() => loadMessages(false), 2000);

loadClients();
