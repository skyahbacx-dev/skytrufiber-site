// ==========================================
// CSR CHAT JAVASCRIPT - FULL FILE
// ==========================================

let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

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

    loadClientInfo();
    loadMessages(true);
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

/******** LOAD CHAT MESSAGES (NO FLICKER) ********/
function loadMessages(initialLoad = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initialLoad) {
            $("#chatMessages").html(""); // reset clean once
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            let newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                let isCSR = (m.sender_type === "csr");
                let side = isCSR ? "csr" : "client";

                let attachment = "";
                if (m.media_url) {
                    if (m.media_type === "image") {
                        attachment = `<img src="${m.media_url}" class="chat-media-img" onclick="openMedia('${m.media_url}')">`;
                    } else if (m.media_type === "video") {
                        attachment = `<video class="chat-media-video" controls><source src="${m.media_url}"></video>`;
                    }
                }

                let statusText = "";
                if (isCSR) {
                    if (m.seen === "1") statusText = `<div class="seen-tag">Seen</div>`;
                    else statusText = `<div class="delivered-tag">Delivered</div>`;
                }

                let html = `
                <div class="msg-row ${side} animate-bubble">
                    <img src="upload/default-avatar.png" class="chat-avatar">
                    <div class="msg-bubble-wrap">
                        <div class="bubble">${m.message || ""} ${attachment}</div>
                        <div class="meta">${m.created_at}</div>
                        ${statusText}
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

/******** PREVIEW FILES ********/
$(".upload-icon").on("click", function () {
    $("#fileInput").click();
});

$("#fileInput").on("change", function (e) {
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
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages(false);
        }
    });
}

/******** MEDIA MODAL ********/
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}

$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** AUTO REFRESH ********/
setInterval(loadClients, 5000);
setInterval(() => loadMessages(false), 1200);

/******** TOGGLE CLIENT INFO PANEL ********/
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}
