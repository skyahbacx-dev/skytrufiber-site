let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

let modalClientID = 0;
let modalClientName = "";

/******** SIDEBAR ********/
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/******** CLIENT LIST ********/
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
    $(`#client-${id}`).addClass("active-client");

    $("#chatName").text(name);

    const locked = assigned && assigned !== csrUser;
    $("#messageInput, #sendBtn").prop("disabled", locked);
    $(".upload-icon").toggle(!locked);

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

/******** LOAD CHAT MESSAGES ********/
function loadMessages(initial = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            let newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";
                const avatar = "upload/default-avatar.png";

                let media = "";
                if (m.media_url) {
                    media =
                        m.media_type === "image"
                            ? `<img src="${m.media_url}" class="file-img">`
                            : `<video class="file-img" controls><source src="${m.media_url}"></video>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <img src="${avatar}" class="msg-avatar">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${media}</div>
                            <div class="meta">${m.created_at}</div>
                        </div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/******** SEND MESSAGE ********/
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
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

/******** UPLOAD PREVIEW ********/
$(".upload-icon").click(() => $("#fileInput").click());

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

/******** ASSIGN MODAL ********/
function openAssignModal(id, name) {
    modalClientID = id;
    modalClientName = name;
    $("#assignTitle").text("Assign Client?");
    $("#assignText").text("Assign " + name + " to you?");
    $("#assignModal").fadeIn(200);
}

function openUnassignModal(id, name) {
    modalClientID = id;
    modalClientName = name;
    $("#assignTitle").text("Unassign Client?");
    $("#assignText").text("Remove " + name + " from your assignment?");
    $("#assignModal").fadeIn(200);
}

function closeAssignModal() {
    $("#assignModal").fadeOut(200);
}

function confirmAssign() {
    let url = $("#assignTitle").text().includes("Unassign")
        ? "unassign_client.php"
        : "assign_client.php";

    $.post(url, { client_id: modalClientID }, () => {
        loadClients();
        closeAssignModal();
    });
}

/******** MEDIA VIEWER ********/
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** AUTO REFRESH ********/
setInterval(loadClients, 3000);
setInterval(() => loadMessages(false), 1200);

loadClients();
