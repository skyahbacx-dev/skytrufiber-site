// ==========================================
// CSR CHAT JAVASCRIPT - FULL FILE
// Typing | Seen/Delivered | Animations | Sound | Smooth scroll
// ==========================================

let selectedClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;
let typingTimer = null;

let audioSend = new Audio("send.mp3");
let audioReceive = new Audio("receive.mp3");

// SIDEBAR
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

// LOAD CLIENT LIST
function loadClients() {
    $.get("client_list.php", data => $("#clientList").html(data));
}

// SELECT CLIENT
function selectClient(id, name) {
    selectedClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#typingIndicator").hide();

    loadClientInfo();
    loadMessages(true);

    $.post("update_read.php", { client_id: selectedClient, csr: csrUser });
}

// LOAD CLIENT INFO
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

// LOAD MESSAGES
function loadMessages(initial = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            let newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = m.sender_type === "csr" ? "csr" : "client";
                const avatarImg = "upload/default-avatar.png";

                let fileHTML = "";
                if (m.media_url) {
                    fileHTML = `<img src="${m.media_url}" class="file-img" onclick="openMedia('${m.media_url}')">`;
                }

                let seenTick = "";
                if (side === "csr") {
                    seenTick = m.seen ? `<span class="seen-checks">✓✓</span>` : `<span class="seen-checks" style="opacity:.4;">✓</span>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side} animate-msg">
                        <img src="${avatarImg}" class="msg-avatar">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${fileHTML}</div>
                            <div class="meta">${m.created_at} ${seenTick}</div>
                        </div>
                    </div>
                `);

                if (side === "client") audioReceive.play();
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;

        $.post("update_read.php", { client_id: selectedClient, csr: csrUser });
    });
}

// SEND MESSAGE
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
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            audioSend.play();
            loadMessages(false);
        }
    });
}

// FILE PREVIEW
$(".upload-icon").click(() => $("#fileInput").click());
$("#fileInput").on("change", e => {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");
    filesToSend.forEach(file => {
        let reader = new FileReader();
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

// OPEN MEDIA
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// TYPING INDICATOR
$("#messageInput").on("input", () => {
    $.post("typing_status.php", { client_id: selectedClient, csr: csrUser, typing: 1 });

    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        $.post("typing_status.php", { client_id: selectedClient, csr: csrUser, typing: 0 });
    }, 1200);
});

// DISPLAY TYPING
function loadTyping() {
    $.get("typing_status.php?client_id=" + selectedClient, status => {
        if (status === "1") $("#typingIndicator").show();
        else $("#typingIndicator").hide();
    });
}

// AUTO REFRESH
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1200);
setInterval(loadTyping, 1000);

loadClients();
