/* =======================================================
   CSR CHAT — FULL JS (MESSENGER SYSTEM FINAL)
==========================================================*/

let activeClient = 0;
let filesToSend = [];
let lastCount = 0;
let isLoading = false;

/* LOAD CLIENT LIST */
function loadClients(search = "") {
    $.get("client_list.php", { search }, function(res) {
        $("#clientList").html(res);
    });
}

/* SELECT CLIENT */
function selectClient(id, name) {
    activeClient = id;
    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* LOAD MESSAGES */
function loadMessages(initial = false) {
    if (!activeClient || isLoading) return;
    isLoading = true;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function(data) {

        if (initial) $("#chatMessages").html("");

        if (data.length > lastCount) {
            const newMsgs = data.slice(lastCount);

            newMsgs.forEach(m => {
                let side = m.sender_type === "csr" ? "csr" : "client";

                let media = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        media = `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else {
                        media = `<video class="file-img" controls><source src="${m.media_path}"></video>`;
                    }
                }

                let tick = "";
                if (m.sender_type === "csr") {
                    if (m.seen) tick = `<span class="tick blue">✓✓</span>`;
                    else if (m.delivered) tick = `<span class="tick">✓✓</span>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${media || ""}</div>
                            <div class="meta">${m.created_at} ${tick}</div>
                        </div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastCount = data.length;
        isLoading = false;
    });
}

/* SEND MESSAGE */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let txt = $("#messageInput").val().trim();
    if (!txt && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", txt);
    fd.append("client_id", activeClient);

    filesToSend.forEach(f => fd.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: () => {
            $("#messageInput").val("");
            $("#fileInput").val("");
            $("#previewArea").html("");
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
                        : `<img src="${ev.target.result}">`
                    }
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* CLIENT INFO PANEL */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, res => {
        $("#infoName").text(res.name);
        $("#infoEmail").text(res.email);
        $("#infoDistrict").text(res.district);
        $("#infoBrgy").text(res.barangay);
    });
}
function toggleClientInfo() { $("#infoPanel").toggleClass("show"); }

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
