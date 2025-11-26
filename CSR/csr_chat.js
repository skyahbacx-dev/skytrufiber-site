/* =======================================================
   CSR CHAT — FINAL WORKING JS (FULL FILE)
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
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
    $.getJSON("client_info.php?id=" + activeClient, (data) => {
        $("#chatAvatar").attr("src", data.avatar || "upload/default-avatar.png");
        $("#chatName").text(data.name);
        $("#chatEmail").text(data.email);
        $("#chatStatus").html(data.is_online ? "<span class='online-dot'></span> Online" : "<span class='offline-dot'></span> Offline");
    });
}

function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (msgs) {
        if (initial) $("#chatMessages").html("");
        if (msgs.length > lastMessageCount) {
            const newMsgs = msgs.slice(lastMessageCount);
            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "me" : "them";
                let attach = "";

                if (m.media_path) {
                    const mediaURL = BASE_MEDIA + m.media_path;
                    attach = (m.media_type === "image")
                        ? `<img src="${mediaURL}" class="msg-img" onclick="openMedia('${mediaURL}')">`
                        : `<video controls class="msg-img"><source src="${mediaURL}"></video>`;
                }

                $("#chatMessages").append(`
                    <div class="message ${side}">
                        ${m.message || ""} 
                        ${attach}
                        <div class="timestamp">${m.created_at}</div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }
        lastMessageCount = msgs.length;
    });
}

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!activeClient) return alert("No client selected!");
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("client_id", activeClient);
    fd.append("message", msg);

    filesToSend.forEach(f => fd.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function () {
            $("#messageInput").val("");
            filesToSend = [];
            $("#previewArea").html("");
            loadMessages(false);
            loadClients();
        },
        error: function (xhr) {
            console.log("Error: ", xhr.responseText);
        }
    });
}

$("#sendBtn").on("click", sendMessage);
$("#messageInput").on("keypress", e => {
    if (e.key === "Enter") sendMessage();
});

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

function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

function assignClient(e, id) {
    e.stopPropagation();
    $.post("assign_client.php", { client_id: id }, () => loadClients());
}

function unassignClient(e, id) {
    e.stopPropagation();
    $.post("unassign_client.php", { client_id: id }, () => loadClients());
}

setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInputMain").val()), 2000);

loadClients();
