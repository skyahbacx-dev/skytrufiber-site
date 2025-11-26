/* =======================================================
   CSR CHAT — FINAL FULL JS
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";
/* =======================================================
   SKYTRU FIBER — CSR CHAT JS (FINAL FULL VERSION)
======================================================= */

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* ======= LOAD CLIENT LIST ======= */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function(data) {
        $("#clientList").html(data);
    });
}

/* ======= SELECT CLIENT ======= */
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

/* ======= LOAD CLIENT INFO ======= */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {

        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        if (!data.assigned_csr) {
            $("#assignBtn").show();
            $("#unassignBtn").hide();
            $("#lockedIcon").hide();
            enableChat(false);

        } else if (data.assigned_csr === csrUser) {
            $("#assignBtn").hide();
            $("#unassignBtn").show();
            $("#lockedIcon").hide();
            enableChat(true);

        } else {
            $("#assignBtn").hide();
            $("#unassignBtn").hide();
            $("#lockedIcon").show();
            enableChat(false);
        }
    });
}

/* ======= ENABLE / DISABLE CHAT INPUT ======= */
function enableChat(enable) {
    $("#messageInput").prop("disabled", !enable);
    $("#sendBtn").prop("disabled", !enable);
    $(".file-upload-icon").toggle(enable);
}

/* ======= LOAD MESSAGES ======= */
function loadMessages(initial = false) {
    if (!activeClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {

        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";

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
                    tick = m.seen
                        ? `<span class="tick blue">✓✓</span>`
                        : `<span class="tick">✓✓</span>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${attach}</div>
                            <div class="meta">${m.created_at} ${tick}</div>
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

/* ======= SEND MESSAGE ======= */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
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
            $("#fileInput").val("");

            loadMessages(false);
            loadClients();
        }
    });
}

/* ======= FILE PREVIEW ======= */
$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`<img src="${ev.target.result}" class="preview-thumb">`);
        };
        reader.readAsDataURL(file);
    });
});

/* ======= ASSIGN / UNASSIGN ======= */
function assignSelected() {
    $.post("assign.php", { client_id: activeClient }, () => {
        loadClients();
        loadClientInfo();
    });
}

function unassignSelected() {
    $.post("unassign.php", { client_id: activeClient }, () => {
        loadClients();
        loadClientInfo();
    });
}

/* ======= MEDIA MODAL ======= */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ======= AUTO REFRESH ======= */
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

loadClients();

