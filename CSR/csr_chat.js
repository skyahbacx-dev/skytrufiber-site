/* =======================================================
   CSR CHAT JAVASCRIPT – FINAL FULL VERSION
   ======================================================= */

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* ================= LOAD CLIENT LIST ================= */
function loadClients(search = "") {
    $.get("client_list.php", { search }, function (html) {
        $("#clientList").html(html);
    });
}

/* ================= SELECT CLIENT ================= */
function selectClient(id, name, assignedTo) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* ================= LOAD CLIENT INFO ================= */
function loadClientInfo() {
    if (!activeClient) return;

    $.getJSON("client_info.php?id=" + activeClient, function (data) {
        $("#infoName").text(data.full_name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* ================= DATE LABEL ================= */
function dateLabel(date) {
    const today = new Date().toDateString();
    const d = new Date(date).toDateString();
    return today === d ? "Today" : new Date(date).toLocaleDateString();
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
            const newMessages = messages.slice(lastMessageCount);

            newMessages.forEach((m, index) => {

                if (index === 0 || dateLabel(m.created_at) !== dateLabel(newMessages[index - 1].created_at)) {
                    $("#chatMessages").append(`<div class="date-separator">${dateLabel(m.created_at)}</div>`);
                }

                const side = m.sender_type === "csr" ? "csr" : "client";

                let attach = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        attach = `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else {
                        attach = `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                    }
                }

                let tick = "";
                if (m.sender_type === "csr") {
                    if (m.seen) tick = `<span class="tick blue">✓✓</span>`;
                    else if (m.delivered) tick = `<span class="tick">✓✓</span>`;
                }

                const html = `
                <div class="msg-row ${side} animate-msg">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${attach}</div>
                        <div class="meta">${m.created_at} ${tick}</div>
                    </div>
                </div>
                `;
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
$("#messageInput").keypress((e) => {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
    fd.append("client_id", activeClient);
    fd.append("message", msg);

    filesToSend.forEach(file => fd.append("media[]", file));

    $.ajax({
        method: "POST",
        url: "save_chat_csr.php",
        data: fd,
        contentType: false,
        processData: false,
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

/* ================= ASSIGN / UNASSIGN ================= */
function showAssignPopup(id) {
    window.assignTarget = id;
    $("#assignPopup").fadeIn(180);
}
function closeAssignPopup() {
    $("#assignPopup").fadeOut(180);
}
function confirmAssign() {
    $.post("assign_client.php", { client_id: window.assignTarget }, () => {
        closeAssignPopup();
        loadClients();
    });
}

function showUnassignPopup(id) {
    window.unassignTarget = id;
    $("#unassignPopup").fadeIn(180);
}
function closeUnassignPopup() {
    $("#unassignPopup").fadeOut(180);
}
function confirmUnassign() {
    $.post("unassign_client.php", { client_id: window.unassignTarget }, () => {
        closeUnassignPopup();
        loadClients();
    });
}

/* ================= AUTO REFRESH ================= */
setInterval(() => loadClients($("#searchInput").val()), 1600);
setInterval(() => loadMessages(false), 1100);

/* INITIAL LOAD */
loadClients();
