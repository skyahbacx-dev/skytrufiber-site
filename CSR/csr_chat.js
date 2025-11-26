/* =========================================================
   CSR CHAT — FULL PRODUCTION VERSION
========================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

$(document).ready(function () {
    loadClients();
});

/* --------------------------------------------------------
   LOAD CLIENT LIST
-------------------------------------------------------- */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

/* SEARCH */
$("#searchInput").on("input", function () {
    loadClients($(this).val());
});

/* --------------------------------------------------------
   SELECT CLIENT
-------------------------------------------------------- */
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

/* --------------------------------------------------------
   LOAD CLIENT INFO RIGHT PANEL
-------------------------------------------------------- */
function loadClientInfo() {
    if (!activeClient) return;

    $.getJSON("client_info.php?id=" + activeClient, function (data) {
        $("#infoName").text(data.full_name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        if (data.assigned === "me") {
            $("#assignContainer").html(`
                <button class="assign-btn no" onclick="unassignClient(${activeClient})">Unassign</button>
            `);
        } else if (data.assigned === "none") {
            $("#assignContainer").html(`
                <button class="assign-btn yes" onclick="assignClient(${activeClient})">Assign Now</button>
            `);
        } else {
            $("#assignContainer").html(`
                <button class="assign-btn lock" disabled>
                    Assigned to another CSR 🔒
                </button>
            `);
        }
    });
}

/* --------------------------------------------------------
   LOAD MESSAGES
-------------------------------------------------------- */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (messages) {

        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = m.sender_type === "csr" ? "me" : "them";

                let mediaHTML = "";
                if (m.media_path) {
                    const url = BASE_MEDIA + m.media_path;
                    if (m.media_type === "image") {
                        mediaHTML = `<img src="${url}" class="msg-img" onclick="openMedia('${url}')">`;
                    } else {
                        mediaHTML = `<video class="msg-video" controls><source src="${url}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="message ${side}">
                        ${m.message || ""}
                        ${mediaHTML}
                        <div class="meta">${m.created_at}</div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/* --------------------------------------------------------
   SEND MESSAGE
-------------------------------------------------------- */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("client_id", activeClient);
    fd.append("message", msg);

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
            loadClients(); // Update unread counts
        }
    });
}

/* --------------------------------------------------------
   FILE HANDLER + PREVIEW
-------------------------------------------------------- */
$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <img src="${ev.target.result}" class="preview-thumb">
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* --------------------------------------------------------
   ASSIGN / UNASSIGN API
-------------------------------------------------------- */
function assignClient(id) {
    $.post("assign_client.php", { client_id: id }, function () {
        loadClientInfo();
        loadClients();
    });
}

function unassignClient(id) {
    $.post("unassign_client.php", { client_id: id }, function () {
        loadClientInfo();
        loadClients();
    });
}

/* --------------------------------------------------------
   RIGHT PANEL TOGGLE
-------------------------------------------------------- */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* --------------------------------------------------------
   MEDIA VIEWER
-------------------------------------------------------- */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* --------------------------------------------------------
   AUTO REFRESH
-------------------------------------------------------- */
setInterval(() => loadMessages(false), 1500);
setInterval(() => loadClients($("#searchInput").val()), 4000);
