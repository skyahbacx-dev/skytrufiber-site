let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;
let typingTimer;
let isTyping = false;
let loadingMessages = false;

/******** SIDEBAR ********/
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/******** LOAD CLIENT LIST ********/
function loadClients() {
    const query = $("#searchInput").val();
    $.get("client_list.php?search=" + query, data => {
        $("#clientList").html(data);
    });
}

/******** SELECT CLIENT ********/
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $(`#client-${id}`).addClass("active-client");

    $("#chatName").text(name);

    const locked = assigned && assigned !== csrUser;
    $("#messageInput, #sendBtn").prop("disabled", locked);
    $(".upload-icon").toggle(!locked);

    loadClientInfo();
    loadMessages(true);
    markAsSeen();
}

/******** LOAD CLIENT INFO ********/
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/******** LOAD CHAT ********/
function loadMessages(initial = false) {
    if (!selectedClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {

        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);
            let playSound = false;

            newMsgs.forEach(msg => {
                const side = msg.sender_type === "csr" ? "csr" : "client";
                const avatar = "upload/default-avatar.png";
                let media = "";

                if (msg.media_url) {
                    media = msg.media_type === "image"
                        ? `<img src="${msg.media_url}" class="file-img" onclick="openMedia('${msg.media_url}')">`
                        : `<video class="file-img" controls><source src="${msg.media_url}"></video>`;
                }

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <img class="msg-avatar" src="${avatar}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${msg.message || ""}${media}</div>
                            <div class="meta">${msg.created_at}</div>
                        </div>
                    </div>
                `);

                if (side === "client") playSound = true;
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);

            if (playSound) document.getElementById("notifySound").play();
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/******** SEND MESSAGE ********/
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    if (!selectedClient) return;
    const msg = $("#messageInput").val();

    if (!msg.trim() && filesToSend.length === 0) return;

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
        success: function() {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages(false);
            markAsSeen();
        }
    });
}

/******** PREVIEW THUMBNAILS ********/
$(".upload-icon").click(() => $("#fileInput").click());
$("#fileInput").on("change", e => {
    $("#previewArea").html("");
    filesToSend = [...e.target.files];
    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => $("#previewArea").append(`
            <div class="preview-thumb">
                ${file.type.includes("video")
                    ? `<video src="${ev.target.result}" muted></video>`
                    : `<img src="${ev.target.result}">`}
            </div>
        `);
        reader.readAsDataURL(file);
    });
});

/******** SEEN SYSTEM ********/
function markAsSeen() {
    if (!selectedClient) return;
    $.post("chat_read.php", { client_id: selectedClient, csr: csrUser });
}

/******** TYPING SYSTEM ********/
$("#messageInput").on("input", function() {
    if (!selectedClient) return;
    if (!isTyping) {
        isTyping = true;
        $.post("typing.php", { client_id: selectedClient, typing: 1 });
    }

    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        isTyping = false;
        $.post("typing.php", { client_id: selectedClient, typing: 0 });
    }, 1000);
});

// CHECK TYPING STATUS
setInterval(() => {
    if (!selectedClient) return;
    $.getJSON("typing.php?client_id=" + selectedClient, res => {
        if (res.typing) {
            $("#typingIndicator").text("Typing...");
        } else {
            $("#typingIndicator").text("");
        }
    });
}, 700);

/******** INFO PANEL ********/
function toggleClientInfo() {
    $("#clientInfoPanel").toggleClass("show");
}

/******** AUTO REFRESH ********/
setInterval(loadClients, 2000);
setInterval(() => loadMessages(false), 1000);

loadClients();
