/* =======================================================
   CSR CHAT — FULL FINAL FILE
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

/* LOAD CLIENT LIST */
function loadClients(search = "") {
    $.get("client_list.php", { search }, function (data) {
        $("#clientList").html(data);
    });
}
function assignClient(id) {
    $.post("assign_client.php", { client_id: id }, function () {
        loadClients();
    });
}

function unassignClient(id) {
    $.post("unassign_client.php", { client_id: id }, function () {
        loadClients();
    });
}

/* SELECT A CLIENT */
function selectClient(id, name) {
    activeClient = id;
    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");

    loadClientInfo();
    loadMessages(true);
}

/* LOAD CLIENT INFO SIDEBAR */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        $("#assignArea").html(data.action_buttons);
    });
}

/* LOAD CHAT MESSAGES */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "me" : "them";

                let mediaHtml = "";
                if (m.media_path) {
                    mediaHtml =
                        m.media_type === "image"
                            ? `<img src="${BASE_MEDIA + m.media_path}" class="chat-img" onclick="openMedia('${BASE_MEDIA + m.media_path}')">`
                            : `<video class="chat-img" controls><source src="${BASE_MEDIA + m.media_path}"></video>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble">${m.message || ""}${mediaHtml}</div>
                        <div class="meta">${m.created_at}</div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/* SEND MESSAGE */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", activeClient);

    filesToSend.forEach(file => fd.append("media[]", file));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        processData: false,
        contentType: false,
        data: fd,
        success: () => {
            $("#messageInput").val("");
            filesToSend = [];
            $("#previewArea").html("");
            loadMessages(false);
            loadClients();
        }
    });
}

/* PREVIEW UPLOAD */
$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`<img src="${ev.target.result}" class="preview-thumb">`);
        };
        reader.readAsDataURL(file);
    });
});

/* MEDIA VIEWER */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* LIVE UPDATES */
setInterval(() => loadMessages(false), 1500);
setInterval(() => loadClients($("#searchInput").val()), 3000);

/* INIT */
loadClients();
