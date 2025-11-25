/* =======================================================
   CSR CHAT — FINAL WHATSAPP STYLE MESSENGER SYSTEM (A)
   ======================================================= */

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* ================= LOAD CLIENT LIST ================= */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

/* ================= SELECT CLIENT ================= */
function selectClient(id, name, assignedTo) {
    activeClient = id;

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

    $.getJSON("client_info.php?id=" + activeClient, function (data) {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* ================= DATE LABEL ================= */
function dateLabel(date) {
    const today = new Date().toDateString();
    const incoming = new Date(date).toDateString();
    return today === incoming ? "Today" : new Date(date).toLocaleDateString();
}

/* ================= LOAD MESSAGES ================= */
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

                if (idx === 0 || dateLabel(m.created_at) !== dateLabel(newMsgs[idx - 1].created_at)) {
                    $("#chatMessages").append(`
                        <div class="date-separator">${dateLabel(m.created_at)}</div>
                    `);
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
                    if (m.seen) {
                        statusHTML = `<span class="tick blue">✓✓</span>`;
                    } else if (m.delivered) {
                        statusHTML = `<span class="tick">✓✓</span>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side} animate-msg">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${attachment}</div>
                            <div class="meta">${m.created_at} ${statusHTML}</div>
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

/* ================= SEND MESSAGE ================= */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const text = $("#messageInput").val().trim();
    if (!text && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", text);
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

/* ================= FILE PREVIEW ================= */
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

/* ================= ASSIGNMENT ================= */
function showAssignPopup(id) { window.assignID = id; $("#assignPopup").fadeIn(200); }
function closeAssignPopup() { $("#assignPopup").fadeOut(200); }

function confirmAssign() {
    $.post("assign_client.php", { client_id: window.assignID }, () => {
        closeAssignPopup(); loadClients();
    });
}

function showUnassignPopup(id) { window.unassignID = id; $("#unassignPopup").fadeIn(200); }
function closeUnassignPopup() { $("#unassignPopup").fadeOut(200); }

function confirmUnassign() {
    $.post("unassign_client.php", { client_id: window.unassignID }, () => {
        closeUnassignPopup(); loadClients();
    });
}

/* ================= RIGHT PANEL TOGGLE ================= */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* ================= MEDIA VIEWER ================= */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ================= AUTO REFRESH ================= */
setInterval(() => loadClients($("#searchInput").val()), 1600);
setInterval(() => loadMessages(false), 1000);

/* INITIAL LOAD */
loadClients();
