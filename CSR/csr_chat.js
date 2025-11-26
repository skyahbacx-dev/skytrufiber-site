/* =======================================================
   CSR CHAT — FULL JS WITH ASSIGN LOGIC + UI FIX
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

function loadClients(search = "") {
    $.get("client_list.php", { search }, function(data) {
        $("#clientList").html(data);
    });
}

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

function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, function(data) {
        $("#infoAvatar").attr("src", data.profile_pic);
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        let buttonHTML = "";

        if (data.assigned_csr === null || data.assigned_csr === "0") {
            buttonHTML = `<button class="action-btn" onclick="assignClient(${activeClient})">➕ Assign to me</button>`;
        } else if (data.assigned_csr == data.currentCsr) {
            buttonHTML = `<button class="action-btn" onclick="unassignClient(${activeClient})">➖ Remove Client</button>`;
        } else {
            buttonHTML = `<div style="font-weight:bold;color:red;">🔒 Assigned to another CSR</div>`;
        }

        $("#infoActions").html(buttonHTML);
    });
}

/* ASSIGN CLIENT */
function assignClient(clientID) {
    $.post("assign_client.php", { client_id: clientID }, function() {
        loadClients();
        loadClientInfo();
    });
}

/* UNASSIGN CLIENT */
function unassignClient(clientID) {
    $.post("unassign_client.php", { client_id: clientID }, function() {
        loadClients();
        loadClientInfo();
    });
}

/* LOAD CHAT MESSAGES */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function(messages) {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);
            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";

                let attach = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        attach = `<img src="${BASE_MEDIA + m.media_path}" class="file-img">`;
                    } else {
                        attach = `<video controls class="file-img"><source src="${BASE_MEDIA + m.media_path}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble">${m.message || ""}${attach}</div>
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
    if (!activeClient) return;

    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", activeClient);

    filesToSend.forEach(f => fd.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: () => {
            $("#messageInput").val("");
            filesToSend = [];
            $("#previewArea").html("");
            loadMessages(false);
            loadClients();
        }
    });
}

/* FILE PREVIEW */
$("#fileInput").on("change", function(event) {
    filesToSend = Array.from(event.target.files);
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            $("#previewArea").append(`<img src="${e.target.result}" class="preview-thumb">`);
        };
        reader.readAsDataURL(file);
    });
});

/* TOGGLE INFO PANEL */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* AUTO REFRESH */
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

/* INIT */
loadClients();
