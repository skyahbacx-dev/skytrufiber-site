// ==========================================
// CSR CHAT JAVASCRIPT - FULL FINAL VERSION
// ==========================================

let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;
let pendingUnassignClientId = null;

/******** SIDEBAR ********/
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
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

    const locked = assigned && assigned !== csrFullname;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);

    if (locked) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
    }

    lastMessageCount = 0;
    loadClientInfo();
    loadMessages(true);
}

/******** CLIENT INFO ********/
function loadClientInfo() {
    if (!selectedClient) return;

    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name ?? "");
        $("#infoEmail").text(info.email ?? "");
        $("#infoDistrict").text(info.district ?? "");
        $("#infoBrgy").text(info.barangay ?? "");
    });
}

/******** LOAD CHAT MESSAGES ********/
function loadMessages(initialLoad = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initialLoad) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            let newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";
                const avatarImg = "upload/default-avatar.png";

                let attachment = "";
                if (m.media_url) {
                    if (m.media_type === "image") {
                        attachment = `<img src="${m.media_url}" class="file-img" onclick="openMedia('${m.media_url}')">`;
                    } else if (m.media_type === "video") {
                        attachment = `<video class="file-img" controls><source src="${m.media_url}"></video>`;
                    }
                }

                let statusIcons = (side === "csr") ? `<span class="seen-checks">✓✓</span>` : ``;

                const html = `
                <div class="msg-row ${side}">
                    <img src="${avatarImg}" class="msg-avatar">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${attachment}</div>
                        <div class="meta">${m.created_at} ${statusIcons}</div>
                    </div>
                </div>`;

                $("#chatMessages").append(html);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/******** PREVIEW UPLOADS ********/
$(".upload-icon").on("click", () => $("#fileInput").click());

$("#fileInput").on("change", e => {
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
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);
    filesToSend.forEach(f => fd.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];

            loadMessages(false);
        }
    });
}

/******** ASSIGNMENT ********/
function assignClient(id) {
    $.post("assign_client.php", { client_id:id }, () => {
        loadClients();
    });
}

function unassignClient(id) {
    pendingUnassignClientId = id;
    $("#confirmOverlay").css("display","flex");
}

/******** POPUP HANDLERS ********/
$("#confirmNo").click(() => {
    $("#confirmOverlay").hide();
    pendingUnassignClientId = null;
});

$("#confirmYes").click(() => {
    $.post("unassign_client.php", { client_id: pendingUnassignClientId }, () => {
        $("#confirmOverlay").hide();
        pendingUnassignClientId = null;
        loadClients();
    });
});

/******** MEDIA VIEWER ********/
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** AUTO REFRESH ********/
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1500);

loadClients();

/******** SLIDE INFO PANEL ********/
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}
