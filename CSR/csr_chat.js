/* =======================================================
   CSR CHAT — FINAL FULL JavaScript
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

/* -------------------------------------------------------
   LOAD CLIENT LIST
------------------------------------------------------- */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (html) {
        $("#clientList").html(html);
    });
}

/* -------------------------------------------------------
   SELECT CLIENT
------------------------------------------------------- */
function selectClient(id, name) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");

    loadClientInfo();
    loadMessages(true);
}

/* Load right panel info */
function loadClientInfo() {
    $.getJSON("client_info.php?client_id=" + activeClient, data => {
        $("#infoName").text(data.full_name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
        $("#chatStatus").html(`<span class="status-dot ${data.is_online ? "online" : "offline"}"></span> ${data.is_online ? "Online" : "Offline"}`);
    });
}

/* -------------------------------------------------------
   LOAD MESSAGES
------------------------------------------------------- */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "me" : "them";
                let mediaHTML = "";

                if (m.media_path) {
                    if (m.media_type === "image") {
                        mediaHTML = `<img src="${BASE_MEDIA + m.media_path}" class="msg-media" onclick="openMedia('${BASE_MEDIA + m.media_path}')">`;
                    } else {
                        mediaHTML = `<video controls class="msg-media"><source src="${BASE_MEDIA + m.media_path}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="message ${side}">
                        ${m.message || ""}
                        ${mediaHTML}
                        <div class="msg-time">${m.created_at}</div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/* -------------------------------------------------------
   SEND TEXT / MEDIA
------------------------------------------------------- */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;
    if (!activeClient) return alert("Select a client first");

    let formData = new FormData();
    formData.append("client_id", activeClient);
    formData.append("message", msg);

    filesToSend.forEach(f => formData.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages(false);
            loadClients();
        }
    });
}

/* -------------------------------------------------------
   FILE PREVIEW
------------------------------------------------------- */
$("#fileInput").on("change", function (e) {
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

/* -------------------------------------------------------
   MEDIA MODAL
------------------------------------------------------- */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* -------------------------------------------------------
   SEARCH CLIENTS
------------------------------------------------------- */
$("#searchInput").on("input", function () {
    loadClients($(this).val());
});

/* -------------------------------------------------------
   AUTO REFRESH
------------------------------------------------------- */
setInterval(() => {
    if (activeClient) loadMessages(false);
}, 1200);

setInterval(() => loadClients($("#searchInput").val()), 3000);

loadClients();
