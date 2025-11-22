// ===============================================
// CSR CHAT — FINAL FULL FILE (STABLE VERSION)
// ===============================================

let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let currentAssignClient = null;
let currentUnassignClient = null;

let lastMessageCount = 0;
let loadingMessages = false;

/* --------------------------------------------
   SIDEBAR TOGGLE
-------------------------------------------- */
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* --------------------------------------------
   LOAD CLIENT LIST
-------------------------------------------- */
function loadClients(search = "") {
    $.get("client_list.php", { search }, function (data) {
        $("#clientList").html(data);
    });
}

/* --------------------------------------------
   SELECT CLIENT
-------------------------------------------- */
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo = assigned || "";

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html(""); // Clear window

    const locked = assignedTo && assignedTo !== csrUser;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    $(".upload-icon").toggle(!locked);

    loadClientInfo();
    loadMessages(true);
}

/* --------------------------------------------
   CLIENT INFO LOAD
-------------------------------------------- */
function loadClientInfo() {
    if (!selectedClient) return;

    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name || "");
        $("#infoEmail").text(info.email || "");
        $("#infoDistrict").text(info.district || "");
        $("#infoBrgy").text(info.barangay || "");
    });
}

/* --------------------------------------------
   SLIDE PANEL TOGGLE
-------------------------------------------- */
function toggleClientInfo() {
    $("#clientInfoPanel").toggleClass("show");
}

/* --------------------------------------------
   LOAD MESSAGES
-------------------------------------------- */
function loadMessages(initialLoad = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, function (messages) {

        if (initialLoad) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            let newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                let side = (m.sender_type === "csr") ? "csr" : "client";

                let attachment = "";
                if (m.media_url) {
                    attachment = `
                        <img src="${m.media_url}" class="file-img"
                        onclick="openMedia('${m.media_url}')">
                    `;
                }

                let html = `
                    <div class="msg-row ${side}">
                        <img src="upload/default-avatar.png" class="msg-avatar">
                        <div>
                            <div class="bubble">${m.message || ""}${attachment}</div>
                            <div class="meta">${m.created_at}</div>
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

/* --------------------------------------------
   SEND MESSAGE
-------------------------------------------- */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => fd.append("files[]", f));

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
            loadClients();
        }
    });
}

/* --------------------------------------------
   IMAGE PREVIEW
-------------------------------------------- */
$(".upload-icon").on("click", () => $("#fileInput").click());

$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    <img src="${ev.target.result}">
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* --------------------------------------------
   MEDIA MODAL VIEW
-------------------------------------------- */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* --------------------------------------------
   ASSIGN / UNASSIGN POPUPS
-------------------------------------------- */
function showAssignPopup(id) {
    currentAssignClient = id;
    $("#assignPopup").fadeIn(150);
}
function closeAssignPopup() { $("#assignPopup").fadeOut(150); }

function confirmAssign() {
    $.post("assign_client.php", { client_id: currentAssignClient }, () => {
        closeAssignPopup();
        loadClients();
        loadMessages(true);
    });
}

function showUnassignPopup(id) {
    currentUnassignClient = id;
    $("#unassignPopup").fadeIn(150);
}
function closeUnassignPopup() { $("#unassignPopup").fadeOut(150); }

function confirmUnassign() {
    $.post("unassign_client.php", { client_id: currentUnassignClient }, () => {
        closeUnassignPopup();
        loadClients();
        loadMessages(true);
        $("#chatMessages").html(`<p class="placeholder-msg">👈 Select a client</p>`);
        selectedClient = 0;
    });
}

/* --------------------------------------------
   AUTO REFRESH LOOP
-------------------------------------------- */
setInterval(() => loadClients($("#searchInput").val()), 3000);
setInterval(() => loadMessages(false), 1200);

loadClients();
