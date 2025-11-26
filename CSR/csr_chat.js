/* =======================================================
   CSR CHAT — FINAL JAVASCRIPT
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

/* LOAD CLIENT LIST */
function loadClients(search = "") {
    $.get("client_list.php", { search }, function (html) {
        $("#clientList").html(html);
    });
}

/* SELECT CLIENT */
function selectClient(id, name) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    $("#statusDot").removeClass("online").addClass("offline");

    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* LOAD CLIENT INFORMATION */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, function (data) {

        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        if (data.profile_pic)
            $("#chatAvatar, #infoAvatar").attr("src", data.profile_pic);

        $("#chatStatus").html(
            `<span id="statusDot" class="status-dot ${data.online ? "online" : "offline"}"></span> ${data.online ? "Online" : "Offline"}`
        );

        handleAssignButtons(data);
    });
}

/* HANDLE ASSIGN/REMOVE BUTTON VISIBILITY */
function handleAssignButtons(data) {
    const box = $("#assignContainer");
    box.show();

    if (!data.assigned_csr) {
        $("#assignLabel").text("Assign this client to you?");
        $("#assignYes").show();
        $("#assignNo").hide();
    }
    else if (data.assigned_csr === data.current_csr) {
        $("#assignLabel").text("Client assigned to you. Remove?");
        $("#assignYes").hide();
        $("#assignNo").show();
    }
    else {
        $("#assignLabel").text(`Assigned to ${data.assigned_csr}`);
        $("#assignYes").hide();
        $("#assignNo").hide();
    }
}

/* ASSIGN / REMOVE EVENTS */
$("#assignYes").click(function () {
    $.post("assign_client.php", { client_id: activeClient }, () => loadClientInfo());
});

$("#assignNo").click(function () {
    $.post("remove_assign.php", { client_id: activeClient }, () => loadClientInfo());
});

/* LOAD MESSAGES */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (msgs) {
        if (initial) $("#chatMessages").html("");

        if (msgs.length > lastMessageCount) {
            const newMsgs = msgs.slice(lastMessageCount);

            newMsgs.forEach(msg => renderMessage(msg));

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = msgs.length;
    });
}

/* RENDER MESSAGE BUBBLES */
function renderMessage(m) {
    let side = (m.sender_type === "csr") ? "me" : "them";
    let media = "";

    if (m.media_path) {
        media = (m.media_type === "image")
            ? `<img class="message-media" src="${BASE_MEDIA + m.media_path}" onclick="openMedia('${BASE_MEDIA + m.media_path}')">`
            : `<video class="message-media" controls><source src="${BASE_MEDIA + m.media_path}"></video>`;
    }

    $("#chatMessages").append(`
        <div class="msg-row ${side}">
            <div class="bubble">${m.message || ""}${media}</div>
            <div class="time">${m.created_at}</div>
        </div>
    `);
}

/* SEND MESSAGE */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
    fd.append("client_id", activeClient);
    fd.append("message", msg);

    filesToSend.forEach(f => fd.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: fd,
        contentType: false,
        processData: false,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages(false);
            loadClients();
        }
    });
}

/* FILE UPLOAD PREVIEW */
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

/* CLIENT INFO SLIDE PANEL */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* MEDIA VIEWER */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* AUTO REFRESH */
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

/* INITIAL LOAD */
loadClients();
