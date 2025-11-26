/* =======================================================
   CSR CHAT — Final Full JS
======================================================= */

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

/* LOAD CLIENT LIST */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function(data) {
        $("#clientList").html(data);
    });
}

/* SELECT CLIENT */
function selectClient(id, name) {
    activeClient = id;
    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* RIGHT INFO PANEL */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* LOAD MESSAGES */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function(messages) {

        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            messages.slice(lastMessageCount).forEach(m => {
                const side = m.sender_type === "csr" ? "csr" : "client";

                let attach = "";
                if (m.media_path) {
                    attach = m.media_type === "image"
                        ? `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`
                        : `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${attach}</div>
                            <div class="meta">${m.created_at}</div>
                        </div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/* SEND */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", activeClient);
    filesToSend.forEach(f => fd.append("media[]", f));

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

/* FILE PREVIEW */
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

/* RIGHT INFO PANEL */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* MEDIA VIEWER */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 3000);

loadClients();
