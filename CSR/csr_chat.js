// csr_chat.js
let selectedClient = 0;
let filesToSend = [];

/* ========== SIDEBAR TOGGLE (KEEPING YOUR EXISTING LAYOUT) ========== */
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.querySelector(".sidebar-overlay");
    if (!sidebar || !overlay) return;

    sidebar.classList.toggle("open");
    overlay.classList.toggle("show");
}

/* ========== SLIDE CLIENT INFO PANEL ========== */
function toggleClientInfo() {
    const panel = document.getElementById("clientInfoPanel");
    if (panel) panel.classList.toggle("show");
}

/* ========== LOAD CLIENT LIST ========== */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* ========== SELECT CLIENT FROM LIST ========== */
// Called by your client_list.php: onclick="selectClient(id,'Name')"
function selectClient(id, name) {
    selectedClient = id;

    $("#chatName").text(name);
    $("#chatStatus").text("Active Chat");
    $("#messageInput").prop("disabled", false);
    $("#sendBtn").prop("disabled", false);

    loadClientInfo();
    loadMessages();
}

/* ========== LOAD CLIENT INFO (SLIDING PANEL) ========== */
function loadClientInfo() {
    if (!selectedClient) return;

    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name || "");
        $("#infoEmail").text(info.email || "");
        $("#infoDistrict").text(info.district || "");
        $("#infoBrgy").text(info.barangay || "");
    });
}

/* ========== LOAD MESSAGES (INCLUDING MEDIA) ========== */
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";

        messages.forEach(m => {
            const side = (m.sender_type === "csr") ? "csr" : "client";

            html += `
                <div class="msg ${side}">
                    <div class="bubble">
                        ${m.message ? escapeHtml(m.message).replace(/\n/g, "<br>") : ""}`;

            // MEDIA DISPLAY
            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<div class="bubble-media">
                                <img src="${m.media_path}" class="file-img" alt="image">
                             </div>`;
                } else if (m.media_type === "video") {
                    html += `<div class="bubble-media">
                                <video controls class="file-video">
                                    <source src="${m.media_path}">
                                    Your browser does not support the video tag.
                                </video>
                             </div>`;
                }
            }

            html += `
                        <div class="meta">${m.created_at}</div>
                    </div>
                </div>`;
        });

        $("#chatMessages").html(html);
        const box = document.getElementById("chatMessages");
        if (box) box.scrollTop = box.scrollHeight;
    });
}

/* ========== PREVIEW MULTIPLE FILES ========== */
$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    const $preview = $("#previewArea");
    $preview.html("");

    if (filesToSend.length === 0) return;

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = function (ev) {
            const isVideo = file.type.startsWith("video/");
            const thumb = isVideo
                ? `<video src="${ev.target.result}" muted></video>`
                : `<img src="${ev.target.result}" alt="">`;

            $preview.append(`
                <div class="preview-item">
                    ${thumb}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* ========== SEND MESSAGE + MEDIA ========== */
$("#sendBtn").on("click", function () {
    if (!selectedClient) return;

    const msg = $("#messageInput").val();
    if (!msg && filesToSend.length === 0) return;

    const formData = new FormData();
    formData.append("message", msg);
    formData.append("client_id", selectedClient);
    formData.append("csr_fullname", csrFullname || "");

    filesToSend.forEach(f => formData.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/* ========== ONLINE STATUS DOT CHECKER ========== */
function checkStatus() {
    if (!selectedClient) return;

    $.getJSON("check_status.php?id=" + selectedClient, res => {
        const $dot = $("#statusDot");
        if (!$dot.length) return;

        $dot.removeClass("online offline");
        if (res && res.status === "online") {
            $dot.addClass("online");
        } else {
            $dot.addClass("offline");
        }
    });
}

/* ========== HELPER: ESCAPE HTML ========== */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* ========== START INTERVALS ========== */
setInterval(loadMessages, 2000);
setInterval(checkStatus, 3000);
loadClients();
