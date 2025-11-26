/* =======================================================
   CSR CHAT — SKYTRUFIBER REALTIME CHAT ENGINE
   Refresh 2 seconds
=========================================================*/

const BASE_MEDIA = "/media/";
const notifySound = new Audio("/audio/notify.mp3");

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

// LOAD CLIENT LIST
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function(data) {
        $("#clientList").html(data);
    });
}

// SELECT CLIENT
function selectClient(id, name) {
    activeClient = id;

    $(".client-item").removeClass("selected");
    $("#client-" + id).addClass("selected");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadClientInfo();
    loadMessages(true);
}

// LOAD CLIENT INFO
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.full_name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        if (data.assigned_csr === csrUser) {
            $("#assignLabel").text("Remove this client?");
            $("#assignYes").text("REMOVE");
            $("#assignYes").off().click(() => unassignClient());
        } else if (data.assigned_csr === null) {
            $("#assignLabel").text("Assign this client to you?");
            $("#assignYes").text("ASSIGN");
            $("#assignYes").off().click(() => assignClient());
        } else {
            $("#assignLabel").text("Assigned to another CSR");
            $("#assignYes").hide();
            $("#assignNo").hide();
        }
    });
}

// ASSIGN / UNASSIGN
function assignClient() {
    $.post("assign.php", { client_id: activeClient }, () => loadClientInfo());
}
function unassignClient() {
    $.post("unassign.php", { client_id: activeClient }, () => loadClientInfo());
}

// LOAD MESSAGES
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, messages => {
        if (initial) $("#chatMessages").empty();

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = m.sender_type === "csr" ? "me" : "them";
                let p = "";

                if (m.media.length > 0) {
                    m.media.forEach(f => {
                        if (f.media_type === "image") {
                            p += `<img src="${BASE_MEDIA}${f.media_path}" class="file-img" onclick="openMedia('${BASE_MEDIA}${f.media_path}')">`;
                        } else {
                            p += `<video class="file-img" controls>
                                    <source src="${BASE_MEDIA}${f.media_path}">
                                  </video>`;
                        }
                    });
                }

                $("#chatMessages").append(`
                    <div class="message ${side}">
                        ${m.message ? m.message : ""}
                        ${p}
                        <div class="meta">${m.created_at}</div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);

            // play sound if message from client
            if (newMsgs.some(n => n.sender_type === "client")) {
                notifySound.play();
            }
        }

        lastMessageCount = messages.length;
    });
}

// SEND MESSAGE
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
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages(false);
            loadClients();
        }
    });
}

// FILE PREVIEW HANDLER
$("#fileInput").on("change", function(e) {
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

function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

// SEARCH
$("#searchInput").on("keyup", function() {
    loadClients($(this).val());
});

// INTERVALS
setInterval(() => loadMessages(false), 2000);
setInterval(() => loadClients($("#searchInput").val()), 2000);

// INITIAL
loadClients();
