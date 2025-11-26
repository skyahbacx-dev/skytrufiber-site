const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

function loadClients(search="") {
    $.get("client_list.php", { search: search }, data => {
        $("#clientList").html(data);
    });
}

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

function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

function loadMessages(initial=false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const sender = (m.sender_type === "csr") ? "me" : "them";
                let media = "";

                if (m.media_path) {
                    media = `<img src="${BASE_MEDIA + m.media_path}" class="file-img">`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${sender}">
                        <div class="bubble">${m.message || ""}${media}</div>
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
        url:"save_chat_csr.php",
        type:"POST",
        data:fd,
        processData:false,
        contentType:false,
        success: () => {
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages(false);
            loadClients();
        }
    });
}

$("#fileInput").change(e => {
    filesToSend = [...e.target.files];
});

setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 2000);

loadClients();
