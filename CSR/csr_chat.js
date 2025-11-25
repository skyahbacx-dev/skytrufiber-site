/* =======================================================
   CSR CHAT — FINAL FULL JS (MESSENGER SYSTEM)
   ======================================================= */

let activeClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* LOAD CLIENT LIST */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

/* SEARCH BAR INPUT EVENT */
$("#searchInput").on("keyup", function () {
    loadClients($(this).val());
});

/* SELECT CLIENT */
function selectClient(id, name, assigned) {
    activeClient = id;
    assignedTo = assigned || "";

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    const locked = assigned && assigned !== csrUser;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    if (locked) $(".file-upload-icon").hide();
    else $(".file-upload-icon").show();

    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* LOAD CLIENT INFO */
function loadClientInfo() {
    if (!activeClient) return;

    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* DATE HEADING */
function formatDate(date) {
    const today = new Date().toDateString();
    const d = new Date(date).toDateString();
    if (today === d) return "Today";
    return new Date(date).toLocaleDateString();
}

/* LOAD CHAT MESSAGES */
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

                const side = (m.sender_type === "csr") ? "csr" : "client";

                let attachment = "";
                if (m.media_path) {
                    attachment = m.media_type === "image"
                        ? `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`
                        : `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                }

                let statusHTML = "";
                if (m.sender_type === "csr") {
                    if (m.seen) statusHTML = `<span class="tick blue">✓✓ Seen</span>`;
                    else if (m.delivered) statusHTML = `<span class="tick">✓✓ Delivered</span>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
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

/* FILE PREVIEW */
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

/* MEDIA VIEWER */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* AUTO REFRESH */
setInterval(() => loadClients($("#searchInput").val()), 2000);
setInterval(() => loadMessages(false), 1200);

/* INITIAL */
loadClients();
