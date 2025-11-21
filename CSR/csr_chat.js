// =====================================================
// CSR CHAT JAVASCRIPT - FULL VERSION WITH:
// ✔ Seen double-check animation
// ✔ Typing indicator
// ✔ Stable scrolling (no flicker)
// ✔ No message duplication
// =====================================================

let selectedClient = 0;
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;
let typingTimeout;

/******** SIDEBAR ********/
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/******** LOAD CLIENT LIST ********/
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/******** SELECT CLIENT ********/
function selectClient(id, name, assigned) {
    selectedClient = id;
    $("#chatName").text(name);

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $(".upload-icon, #messageInput, #sendBtn").prop("disabled", false);

    $("#typingIndicator").remove();
    loadClientInfo();
    loadMessages(true);
}

/******** CLIENT INFO ********/
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/******** LOAD CHAT ********/
function loadMessages(initialLoad = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initialLoad) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            let newMessages = messages.slice(lastMessageCount);

            newMessages.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";
                const avatarImg = "upload/default-avatar.png";

                let attachment = "";
                if (m.media_url) {
                    if (m.media_type === "image") {
                        attachment = `<img src="${m.media_url}" class="file-img" onclick="openMedia('${m.media_url}')">`;
                    } else if (m.media_type === "video") {
                        attachment = `<video class="file-img" controls><source src="${m.media_url}"></video>`;
                    }
                }

                let statusIcons = "";
                if (side === "csr") {
                    statusIcons = `<span class="seen-checks">✔✔</span>`;
                }

                const bubble = `
                <div class="msg-row ${side} animate-msg">
                    <img src="${avatarImg}" class="msg-avatar">
                    <div class="bubble-wrapper">
                        <div class="bubble">${m.message || ""} ${attachment}</div>
                        <div class="meta">${m.created_at} ${statusIcons}</div>
                    </div>
                </div>`;

                $("#chatMessages").append(bubble);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/******** SEND MESSAGE ********/
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => {
    if (e.key === "Enter") sendMessage();
    sendTyping();
});

function sendMessage() {
    let msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);

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
            stopTyping();
        }
    });
}

/******** TYPING INDICATOR ********/
function sendTyping() {
    $.post("typing.php", { client_id: selectedClient, csr_typing: 1 });

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(stopTyping, 1200);
}

function stopTyping() {
    $.post("typing.php", { client_id: selectedClient, csr_typing: 0 });
}

/******** LIVE TYPING POLLER ********/
setInterval(() => {
    if (!selectedClient) return;
    $.get("check_typing.php?client_id=" + selectedClient, status => {
        if (status === "1") {
            if ($("#typingIndicator").length === 0) {
                $("#chatMessages").append(`<div id="typingIndicator" class="typing-bubble">Typing...</div>`);
                $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
            }
        } else {
            $("#typingIndicator").remove();
        }
    });
}, 600);

/******** PREVIEW FILES ********/
$(".upload-icon").on("click", () => $("#fileInput").click());

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
                </div>`);
        };
        reader.readAsDataURL(file);
    });
});

/******** MEDIA MODAL ********/
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/******** AUTO REFRESH ********/
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1500);

/******** CLIENT INFO PANEL ********/
function toggleClientInfo() {
    $("#clientInfoPanel").toggleClass("show");
}
