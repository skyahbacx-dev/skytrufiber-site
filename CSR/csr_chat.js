/* =======================================================
   CSR CHAT – FINAL FULL VERSION
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";
let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

// Load clients
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

// Select client
function selectClient(id, name) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadClientInfo();
    loadMessages(true);
}

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

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (messages) {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);
            newMsgs.forEach(m => {

                const side = (m.sender_type === "csr") ? "csr" : "client";

                let attach = "";
                if (m.media_path) {
                    if (m.media_type === "image") {
                        attach = `<img src="${BASE_MEDIA + m.media_path}" class="file-img" onclick="openMedia('${BASE_MEDIA + m.media_path}')">`;
                    } else {
                        attach = `<video class="file-img" controls><source src="${BASE_MEDIA + m.media_path}"></video>`;
                    }
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble">${m.message || ""}${attach}</div>
                        <div class="meta">${m.created_at}</div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

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
            loadMessages(false);
            loadClients();
        }
    });
}

$("#fileInput").on("change", function (e) {
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

// Assign & Unassign AJAX
function assignClient(id, e) {
    e.stopPropagation();
    $.post("assign_client.php", { client_id: id }, loadClients);
}

function unassignClient(id, e) {
    e.stopPropagation();
    $.post("unassign_client.php", { client_id: id }, loadClients);
}

// Media Viewer
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// Auto refresh
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

// Init load
loadClients();
