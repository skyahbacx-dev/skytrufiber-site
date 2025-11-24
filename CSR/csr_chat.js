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

        // Auto-select first client if none is selected
        if (!selectedClient) {
            let first = $(".client-item").first();
            if (first.length > 0) {
                const cid = first.attr("id").replace("client-", "");
                const name = first.find(".client-name").contents().first().text().trim();
                const assigned = first.attr("data-assigned") || "";
                selectClient(cid, name, assigned);
            }
        }
    });
}

// =======================================
// SELECT CLIENT
// =======================================
function selectClient(id, name, assignedTo) {
    selectedClient = parseInt(id);

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");

    lastMessageCount = 0;

    loadMessages(true);
}

// =======================================
// LOAD MESSAGES
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
                const side = (m.sender_type === "csr") ? "csr" : "client";
                let mediaHTML = "";

                if (m.media_path) {
                    if (m.media_type === "image") {
                        mediaHTML = `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else {
                        mediaHTML = `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                    }
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
// FILE PREVIEW HANDLER
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
// MEDIA VIEWER
// =======================================
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}

$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// =======================================
// AUTO REFRESH
// =======================================
setInterval(() => loadClients(), 2000);
setInterval(() => loadMessages(false), 1200);

// INITIAL LOAD
loadClients();
