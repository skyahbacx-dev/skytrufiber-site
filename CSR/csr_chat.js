/* =======================================================
   CSR CHAT — FINAL COMPLETE JS
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;
let selectedClientAssigned = "";

/* LOAD CLIENT LIST */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
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

/* LOAD CLIENT INFO PANEL */
function loadClientInfo() {
    if (!activeClient) return;

    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        selectedClientAssigned = data.assigned_csr;

        const locked = selectedClientAssigned && selectedClientAssigned !== csrUser;

        $("#messageInput").prop("disabled", locked);
        $("#sendBtn").prop("disabled", locked);
        $(".file-upload-icon").toggle(!locked);
    });
}

/* LOAD MESSAGES */
function loadMessages(initial = false) {
    if (!activeClient || loadingMessages) return;

    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (messages) {

        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";

                let attachment = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        attachment = `<img src="${BASE_MEDIA + m.media_path}" class="file-img" onclick="openMedia('${BASE_MEDIA + m.media_path}')">`;
                    } else {
                        attachment = `<video class="file-img" controls><source src="${BASE_MEDIA + m.media_path}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${attachment}</div>
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

/* SEND MESSAGE */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", activeClient);

    filesToSend.forEach(f => fd.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];

            loadMessages(false);
            loadClients(); // refresh unread
        }
    });
}

/* FILE PREVIEW */
$(".file-upload-icon").click(() => $("#fileInput").click());
$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
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

/* MEDIA VIEWER */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ASSIGN / UNASSIGN POPUP */
function showAssignPopup(id) {
    window.assignTarget = id;
    $("#assignPopup").fadeIn(160);
}
function confirmAssign() {
    $.post("assign_client.php", { client_id: window.assignTarget }, () => {
        $("#assignPopup").fadeOut(160);
        loadClients();
    });
}

function showUnassignPopup(id) {
    window.unassignTarget = id;
    $("#unassignPopup").fadeIn(160);
}
function confirmUnassign() {
    $.post("unassign_client.php", { client_id: window.unassignTarget }, () => {
        $("#unassignPopup").fadeOut(160);
        loadClients();
    });
}

/* RIGHT SIDE PANEL */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* AUTO REFRESH */
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

/* INITIAL */
loadClients();
