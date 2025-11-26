/* =======================================================
   CSR CHAT — FINAL FULL JS (With Date Grouping + Status)
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageDate = "";
let lastMessageCount = 0;

/* ----------------- LOAD CLIENT LIST ----------------- */
function loadClients(search = "") {
    $.get("client_list.php", { search }, function (data) {
        $("#clientList").html(data);
    });
}

/* ----------------- SELECT A CLIENT ------------------ */
function selectClient(id, name) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");

    lastMessageDate = "";
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* ----------------- LOAD CLIENT INFO ------------------ */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* ----------------- FORMAT DATES ------------------ */
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    const d = date.toDateString();
    if (d === today.toDateString()) return "Today";
    if (d === yesterday.toDateString()) return "Yesterday";

    return date.toLocaleDateString("en-US", {
        month: "long",
        day: "numeric",
        year: "numeric"
    });
}

/* ----------------- LOAD CHAT MESSAGES ------------------ */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {
        if (initial) {
            $("#chatMessages").html("");
            lastMessageDate = "";
            lastMessageCount = 0;
        }

        messages.slice(lastMessageCount).forEach(m => {
            const messageDate = formatDate(m.created_at);

            if (messageDate !== lastMessageDate) {
                $("#chatMessages").append(
                    `<div class="date-separator">${messageDate}</div>`
                );
                lastMessageDate = messageDate;
            }

            const side = m.sender_type === "csr" ? "csr" : "client";
            let attach = "";

            if (m.media_path) {
                const mediaURL = BASE_MEDIA + m.media_path;
                if (m.media_type === "image") {
                    attach = `<img src="${mediaURL}" class="file-img" onclick="openMedia('${mediaURL}')">`;
                } else {
                    attach = `<video class="file-img" controls>
                                <source src="${mediaURL}">
                              </video>`;
                }
            }

            let statusHTML = "";
            if (side === "csr") {
                if (m.seen) {
                    statusHTML = `<span class="tick blue">✓✓</span>`;
                } else if (m.delivered) {
                    statusHTML = `<span class="tick">✓✓</span>`;
                }
            }

            $("#chatMessages").append(`
                <div class="msg-row ${side}">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${attach}</div>
                        <div class="meta">
                            ${new Date(m.created_at).toLocaleTimeString([], {hour: "2-digit", minute: "2-digit"})}
                            ${statusHTML}
                        </div>
                    </div>
                </div>
            `);
        });

        lastMessageCount = messages.length;
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* ----------------- SEND MESSAGE ------------------ */
function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", activeClient);

    filesToSend.forEach(f => fd.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        processData: false,
        contentType: false,
        data: fd,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            $("#fileInput").val("");

            loadMessages(false);
            loadClients();
        }
    });
}

$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

/* ----------------- FILE PREVIEW ------------------ */
$("#fileInput").on("change", e => {
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

/* ----------------- RIGHT PANEL ------------------ */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* ----------------- MEDIA VIEWER ------------------ */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ----------------- REFRESHES ------------------ */
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

/* ----------------- INITIAL LOAD ------------------ */
loadClients();
