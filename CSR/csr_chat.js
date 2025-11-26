/* =======================================================
   CSR CHAT - FINAL JS (Matches Neon DB + Your File Names)
======================================================= */

const BASE_MEDIA = "https://s3.us-east-005.backblazeb2.com/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let typingTimer = null;

/* LOAD CLIENT LIST */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function(data) {
        $("#clientList").html(data);
    });
}

/* SELECT CLIENT */
function selectClient(id, name, assignedTo) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    $("#chatMessages").html("");
    lastMessageCount = 0;

    const locked = assignedTo && assignedTo !== csrUser;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    $(".file-upload-icon").toggle(!locked);

    loadMessages(true);
    loadClientInfo(id);
}

/* CLIENT INFO PANEL */
function loadClientInfo(clientID) {
    $.getJSON("client_info.php?id=" + clientID, function(data) {
        $("#infoName").text(data.full_name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* LOAD CHAT */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function(messages) {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {

            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = m.sender_type === "csr" ? "csr" : "client";

                let attach = "";
                if (m.media) {
                    m.media.forEach(f => {
                        if (f.media_type === "image") {
                            attach += `<img src="${BASE_MEDIA + f.media_path}" class="file-img" onclick="openMedia('${BASE_MEDIA + f.media_path}')">`;
                        } else {
                            attach += `<video class="file-img" controls><source src="${BASE_MEDIA + f.media_path}"></video>`;
                        }
                    });
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

/* SEND MESSAGE */
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
        data: fd,
        contentType: false,
        processData: false,
        success: () => {
            $("#messageInput").val("");
            filesToSend = [];
            $("#previewArea").html("");
            $("#fileInput").val("");

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

/* VIEW MEDIA */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* TYPING INDICATOR - EVERY 1 SECOND */
$("#messageInput").on("input", function() {
    clearTimeout(typingTimer);

    fetch("typing_update.php", {
        method: "POST",
        body: new URLSearchParams({ client_id: activeClient, csr_typing: 1 })
    });

    typingTimer = setTimeout(() => {
        fetch("typing_update.php", {
            method: "POST",
            body: new URLSearchParams({ client_id: activeClient, csr_typing: 0 })
        });
    }, 1000);
});

/* SHOW / HIDE CLIENT INFO PANEL */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* AUTO REFRESH */
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

/* INIT */
loadClients();
