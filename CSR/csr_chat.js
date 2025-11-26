/* =======================================================
   CSR CHAT — FINAL FULL JS WITH TYPING & UNREAD COUNTER
======================================================== */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

// Load clients
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, data => {
        $("#clientList").html(data);
    });
}

// Select a client to chat with
function selectClient(id, name) {
    activeClient = id;
    lastMessageCount = 0;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    $("#typingIndicator").hide();

    loadClientInfo();
    loadMessages(true);
}

// Load client details in side panel
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

// Load chat messages
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "me" : "them";

                let attachment = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        attachment = `<img src="${BASE_MEDIA + m.media_path}" class="file-img" onclick="openMedia('${BASE_MEDIA + m.media_path}')">`;
                    } else {
                        attachment = `<video class="file-img" controls>
                            <source src="${BASE_MEDIA + m.media_path}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${m.message || ""}${attachment}</div>
                            <div class="meta">${m.created_at}</div>
                        </div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

// Send message & media
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
            loadMessages(false);
            loadClients();
        }
    });
}

$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => {
    if (e.key === "Enter") sendMessage();
    updateTyping(true);
});

// File upload preview
$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev =>
            $("#previewArea").append(`<img src="${ev.target.result}" class="preview-thumb">`);
        reader.readAsDataURL(file);
    });
});

// Typing indicator handler
function updateTyping(isTyping) {
    if (!activeClient) return;
    $.post("typing_status.php", { client_id: activeClient, is_typing: isTyping, sender: "csr" });
}

function checkTyping() {
    if (!activeClient) return;
    $.get("typing_status.php?client_id=" + activeClient, res => {
        if (res === "typing") $("#typingIndicator").show();
        else $("#typingIndicator").hide();
    });
}

// Media viewer
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}

$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// Auto refresh loops
setInterval(() => loadMessages(false), 1200);
setInterval(checkTyping, 800);
setInterval(() => loadClients($("#searchInput").val()), 2000);

// Init load
loadClients();
