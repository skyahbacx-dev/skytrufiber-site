/* =======================================================
   CSR CHAT — FULL MESSENGER SYSTEM JS (FINAL RELEASE)
   ======================================================= */

let activeClient = 0;
let selectedClientAssigned = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* ================= SIDEBAR ================= */
function toggleSidebar() {
    document.querySelector(".sidebar")?.classList.toggle("open");
    document.querySelector(".sidebar-overlay")?.classList.toggle("show");
}

/* ================= LOAD CLIENT LIST ================= */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

/* ================= SELECT CLIENT ================= */
function selectClient(id, name, assignedTo) {
    activeClient = id;
    selectedClientAssigned = assignedTo || "";

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    const locked = assignedTo && assignedTo !== csrUser;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    $(".file-upload-icon").toggle(!locked);

    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* ================= LOAD CLIENT INFO ================= */
function loadClientInfo() {
    if (!activeClient) return;

    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.client_name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* ================= DATE SEPARATOR ================= */
function dateLabel(date) {
    const today = new Date().toDateString();
    const d = new Date(date).toDateString();
    if (today === d) return "Today";
    return new Date(date).toLocaleDateString();
}

/* ================= LOAD CHAT MESSAGES ================= */
function loadMessages(initial = false) {
    if (!activeClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (messages) {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach((m, idx) => {

                if (idx === 0 || dateLabel(m.created_at) !== dateLabel(newMsgs[idx - 1]?.created_at)) {
                    $("#chatMessages").append(`<div class="date-separator">${dateLabel(m.created_at)}</div>`);
                }

                const side = (m.sender_type === "csr") ? "csr" : "client";

                let attachment = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        attachment = `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else {
                        attachment = `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                    }
                }

                let statusHTML = "";
                if (m.sender_type === "csr") {
                    if (m.seen) statusHTML = `<span class="tick blue">✓✓</span>`;
                    else if (m.delivered) statusHTML = `<span class="tick">✓✓</span>`;
                }

                const html = `
                <div class="msg-row ${side} animate-msg">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${attachment}</div>
                        <div class="meta">${m.created_at} ${statusHTML}</div>
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

/* ================= SEND MESSAGE ================= */
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

/* ================= PREVIEW FILES ================= */
$(".file-upload-icon").click(() => $("#fileInput").click());

$("#fileInput").on("change", e => {
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

/* ================= ASSIGN & UNASSIGN ================= */
function showAssignPopup(id) { window.assignTarget = id; $("#assignPopup").fadeIn(160); }
function closeAssignPopup() { $("#assignPopup").fadeOut(160); }

function confirmAssign() {
    $.post("assign_client.php", { client_id: window.assignTarget }, () => {
        closeAssignPopup(); loadClients();
    });
}

function showUnassignPopup(id) { window.unassignTarget = id; $("#unassignPopup").fadeIn(160); }
function closeUnassignPopup() { $("#unassignPopup").fadeOut(160); }

function confirmUnassign() {
    $.post("unassign_client.php", { client_id: window.unassignTarget }, () => {
        closeUnassignPopup(); loadClients();
    });
}

/* ================= MEDIA VIEWER ================= */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ================= AUTO REFRESH ================= */
setInterval(() => loadClients($("#searchInput").val()), 2000);
setInterval(() => loadMessages(false), 1200);

/* START */
loadClients();
