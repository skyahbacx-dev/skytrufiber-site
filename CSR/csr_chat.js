/*************************************************
 * CSR CHAT JAVASCRIPT (FINAL BUILD - A3 + Anim)
 *************************************************/

let selectedClient = null;
let assignedCSR = null;
let messagesLoaded = [];
let autoScrollEnabled = true;
let typingTimeout = null;
let polling = null;

// CONSTANTS
const POLL_INTERVAL = 1500;

/*************************************************
 * SIDEBAR TOGGLE (Mobile support)
 *************************************************/
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/*************************************************
 * LOAD CLIENT LIST + UNREAD BADGES
 *************************************************/
function loadClients() {
    $.get("load_clients.php", function(data) {
        $("#clientList").html(data);
    });
}
loadClients();
setInterval(loadClients, 4000);

/*************************************************
 * SELECT CLIENT
 *************************************************/
function selectClient(id, name, assignedTo) {
    selectedClient = id;
    assignedCSR = assignedTo;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    messagesLoaded = [];

    updateAssignButtons();
    loadClientInfo();
    loadMessages(true);

    if (polling) clearInterval(polling);
    polling = setInterval(loadMessages, POLL_INTERVAL);
}

/*************************************************
 * UPDATE RIGHT SLIDE CLIENT INFO
 *************************************************/
function loadClientInfo() {
    if (!selectedClient) return;
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name || "");
        $("#infoEmail").text(info.email || "");
        $("#infoDistrict").text(info.district || "");
        $("#infoBrgy").text(info.barangay || "");
    });
}

/*************************************************
 * UPDATE ASSIGN / UNASSIGN / LOCK BUTTON
 *************************************************/
function updateAssignButtons() {
    const isMine = assignedCSR === csrUser;
    const unassigned = !assignedCSR;

    $("#assignBtn, #unassignBtn, #lockedBtn").hide();

    if (unassigned)
        $("#assignBtn").show();
    else if (isMine)
        $("#unassignBtn").show();
    else
        $("#lockedBtn").show();

    $("#messageInput, #sendBtn, .upload-icon").prop("disabled", !isMine);
}

/*************************************************
 * ASSIGN CONTROLS
 *************************************************/
$("#assignBtn").on("click", function () {
    $.post("assign_client.php", { client_id: selectedClient }, () => {
        assignedCSR = csrUser;
        updateAssignButtons();
        loadClients();
    });
});

$("#unassignBtn").on("click", function () {
    $.post("unassign_client.php", { client_id: selectedClient }, () => {
        assignedCSR = "";
        updateAssignButtons();
        loadClients();
    });
});

/*************************************************
 * MESSAGE POLLING + ANIMATION INSERT
 *************************************************/
function loadMessages(forceScroll = false) {
    if (!selectedClient) return;

    $.getJSON("update_read.php?client_id=" + selectedClient, msgs => {
        if (!msgs) return;

        if (msgs.length === messagesLoaded.length) return;

        let newMessages = msgs.slice(messagesLoaded.length);

        newMessages.forEach(m => {
            const side = (m.sender_type === "csr") ? "csr" : "client";
            const avatar = `upload/default-avatar.png`;

            let attachment = "";
            if (m.media_url) {
                if (m.media_type === "image") {
                    attachment = `<img src="${m.media_url}" class="file-img" onclick="openMedia('${m.media_url}')">`;
                } else if (m.media_type === "video") {
                    attachment = `
                        <video class="file-img" controls>
                            <source src="${m.media_url}">
                        </video>`;
                }
            }

            $("#chatMessages").append(`
                <div class="msg-row ${side} animate-msg">
                    <img src="${avatar}" class="msg-avatar">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""}${attachment}</div>
                        <div class="meta">${m.created_at}</div>
                    </div>
                </div>
            `);
        });

        messagesLoaded = msgs;

        if (autoScrollEnabled || forceScroll) {
            let box = $("#chatMessages")[0];
            box.scrollTop = box.scrollHeight;
        }
    });
}

/*************************************************
 * SCROLL CONTROL — Disable auto scroll if user scrolls up
 *************************************************/
$("#chatMessages").on("scroll", function () {
    let box = $("#chatMessages")[0];
    autoScrollEnabled = (box.scrollTop + box.clientHeight + 60 >= box.scrollHeight);
});

/*************************************************
 * MESSAGE SENDING + FILE UPLOAD PREVIEW
 *************************************************/
let filesToSend = [];

$(".upload-icon").on("click", () => $("#fileInput").click());

$("#fileInput").on("change", function(e){
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video") ?
                        `<video src="${ev.target.result}" muted></video>` :
                        `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    if (!selectedClient) return;
    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => fd.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: () => {
            $("#messageInput").val("");
            $("#fileInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages(true);
        }
    });
}

/*************************************************
 * MEDIA VIEWER MODAL
 *************************************************/
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").on("click", () => $("#mediaModal").removeClass("show"));

/*************************************************
 * CLIENT INFO SLIDE PANEL
 *************************************************/
function toggleClientInfo() {
    $("#clientInfoPanel").toggleClass("open");
}
