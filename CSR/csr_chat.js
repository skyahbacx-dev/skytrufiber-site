// =======================================================
// CSR CHAT — FULL PRODUCTION VERSION
// =======================================================

let selectedClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

// =======================================
// LOAD CLIENT LIST
// =======================================
function loadClients() {
    $.get("client_list.php", function (data) {
        $("#clientList").html(data);
    });
}

// =======================================
// SELECT CLIENT
// =======================================
function selectClient(id, name, assignedTo) {
    selectedClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");

    lastMessageCount = 0;
    loadMessages(true);
}

// =======================================
// LOAD CHAT MESSAGES
// =======================================
function loadMessages(initial = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, (messages) => {
        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach((m) => {
                const side = m.sender_type === "csr" ? "csr" : "client";

                let mediaHTML = "";
                if (m.media && Array.isArray(m.media)) {
                    m.media.forEach(file => {
                        if (file.media_type === "image") {
                            mediaHTML += `<img src="${file.media_path}" class="file-img" onclick="openMedia('${file.media_path}')">`;
                        } else {
                            mediaHTML += `<video class="file-img" controls><source src="${file.media_path}"></video>`;
                        }
                    });
                }

                const html = `
                <div class="msg-row ${side}">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${mediaHTML}</div>
                        <div class="meta">${m.created_at}</div>
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

// =======================================
// SEND MESSAGE
// =======================================
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress((e) => {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);

    filesToSend.forEach(f => fd.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        processData: false,
        contentType: false,
        data: fd,
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];

            loadMessages(false);
            loadClients();
        }
    });
}

// =======================================
// FILE PREVIEW
// =======================================
$(".upload-icon").click(() => $("#fileInput").click());

$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach((file) => {
        let reader = new FileReader();
        reader.onload = (ev) => {
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

// =======================================
// AUTO REFRESH
// =======================================
setInterval(() => loadClients(), 2000);
setInterval(() => loadMessages(false), 1200);

// INITIAL LOAD
loadClients();
