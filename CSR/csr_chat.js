/* =======================================================
   CSR CHAT — MESSENGER SYSTEM (FINAL FULL JS)
   ======================================================= */

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* ========================= SIDEBAR ========================= */
function toggleSidebar() {
    document.querySelector(".sidebar")?.classList.toggle("open");
    document.querySelector(".sidebar-overlay")?.classList.toggle("show");
}

/* ======================= LOAD CLIENT LIST ======================== */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);

        // Auto-select first available client if none selected
        if (activeClient === 0) {
            const firstElem = $("#clientList .client-item").first();
            if (firstElem.length) {
                const cid = firstElem.data("id");
                const cname = firstElem.data("name");
                const asg = firstElem.data("assigned");
                selectClient(cid, cname, asg);
            }
        }
    });
}

/* ======================= SELECT CLIENT ======================== */
function selectClient(id, name, assignedTo) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $(`#client-${id}`).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadClientInfo();
    loadMessages(true);

    // Lock input if assigned to other CSR
    const locked = assignedTo && assignedTo !== csrUser;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    $(".file-upload-icon").toggle(!locked);
}

/* ===================== LOAD CLIENT INFO ===================== */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, function (data) {
        if (!data) return;

        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* ======================== DATE LABEL ======================== */
function dateLabel(date) {
    const today = new Date().toDateString();
    const d = new Date(date).toDateString();
    return d === today ? "Today" : new Date(date).toLocaleDateString();
}

/* =================== LOAD CHAT MESSAGES ==================== */
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

            newMsgs.forEach((m, index) => {
                if (index === 0 || dateLabel(m.created_at) !== dateLabel(newMsgs[index - 1].created_at)) {
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

                let tick = "";
                if (m.sender_type === "csr") {
                    if (m.seen) tick = `<span class="tick blue">✓✓</span>`;
                    else if (m.delivered) tick = `<span class="tick">✓✓</span>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side} animate-msg">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${attachment}</div>
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

/* ========================= SEND MESSAGE ========================= */
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
        contentType: false,
        processData: false,
        data: fd,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];

            loadMessages(false);
            loadClients();
        }
    });
}

/* ======================== FILE PREVIEW ======================== */
$(".file-upload-icon").click(() => $("#fileInput").click());

$("#fileInput").change(e => {
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

/* ====================== INFO PANEL ===================== */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* ====================== MEDIA VIEWER ===================== */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* =================== ASSIGN / UNASSIGN =================== */
function showAssignPopup(id) {
    window.assignTarget = id;
    $("#assignPopup").fadeIn(160);
}
function closeAssignPopup() { $("#assignPopup").fadeOut(160); }

function confirmAssign() {
    $.post("assign_client.php", { client_id: window.assignTarget }, () => {
        closeAssignPopup();
        loadClients();
    });
}

function showUnassignPopup(id) {
    window.unassignTarget = id;
    $("#unassignPopup").fadeIn(160);
}
function closeUnassignPopup() { $("#unassignPopup").fadeOut(160); }

function confirmUnassign() {
    $.post("unassign_client.php", { client_id: window.unassignTarget }, () => {
        closeUnassignPopup();
        loadClients();
    });
}

/* ======================= AUTO REFRESH ======================= */
setInterval(() => loadClients($("#searchInput").val()), 2000);
setInterval(() => loadMessages(false), 1200);

/* INITIAL */
loadClients();
