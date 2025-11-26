/* =======================================================
   CSR CHAT SYSTEM - FULL JS (Final Updated)
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

let activeClient = null;
let filesToSend = [];
let lastMessageCount = 0;

/* Load Clients List */
function loadClients(search = "") {
    $.get("client_list.php", { search }, function (html) {
        $("#clientList").html(html);
    });
}

/* Select client to chat */
function selectClient(id) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $.getJSON("client_info.php?id=" + id, data => {
        $("#chatName").text(data.full_name);
        $("#chatAvatar").attr("src", data.avatar || "upload/default-avatar.png");

        $("#infoAvatar").attr("src", data.avatar || "upload/default-avatar.png");
        $("#infoName").text(data.full_name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);

        $("#assignLabel").text(
            data.assigned_csr === "<?= $csrUser ?>"
                ? "Remove this client from you?"
                : "Assign this client to you?"
        );
    });

    $("#chatMessages").html("");
    lastMessageCount = 0;
    loadMessages(true);
}

/* Load chat messages */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, msgs => {

        if (initial) $("#chatMessages").html("");

        if (msgs.length > lastMessageCount) {
            msgs.slice(lastMessageCount).forEach(m => renderMessage(m));
            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = msgs.length;
    });
}

/* Render message bubble */
function renderMessage(m) {
    let side = (m.sender_type === "csr") ? "me" : "them";
    let mediaHTML = "";

    if (m.media && m.media.length > 0) {
        m.media.forEach(f => {
            if (f.media_type === "image") {
                mediaHTML += `<img class="chat-media" onclick="openMedia('${BASE_MEDIA + f.media_path}')" src="${BASE_MEDIA + f.media_path}">`;
            } else {
                mediaHTML += `
                    <video class="chat-media" controls>
                        <source src="${BASE_MEDIA + f.media_path}">
                    </video>`;
            }
        });
    }

    $("#chatMessages").append(`
        <div class="chat-row ${side}">
            <div class="bubble">
                ${m.message ?? ""}
                ${mediaHTML}
                <div class="timestamp">${m.created_at}</div>
            </div>
        </div>
    `);
}

/* Send Message */
$("#sendBtn").on("click", sendMessage);
$("#messageInput").keypress(e => {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    if (!activeClient) return;

    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    let form = new FormData();
    form.append("client_id", activeClient);
    form.append("message", msg);
    form.append("sender_type", "csr");

    filesToSend.forEach(f => form.append("media[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: form,
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

/* Media preview */
$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").empty();

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = evt => {
            $("#previewArea").append(
                `<img class="preview-thumb" src="${evt.target.result}">`
            );
        };
        reader.readAsDataURL(file);
    });
});

/* Media modal viewer */
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* Assign Client to CSR */
$("#assignYes").click(() => modifyAssign(1));
$("#assignNo").click(() => modifyAssign(0));

function modifyAssign(assign) {
    $.post("assign_client.php", {
        client_id: activeClient,
        assign: assign
    }, () => {
        loadClients();
        loadClientInfo();
    });
}

function loadClientInfo() {
    if (activeClient)
        $.getJSON("client_info.php?id=" + activeClient, d => $("#assignLabel").text(
            d.assigned_csr ? "Remove this client from you?" : "Assign this client to you?"
        ));
}

$("#infoBtn").click(() => $("#infoPanel").toggleClass("show"));

// Realtime polling
setInterval(() => loadMessages(false), 1200);
setInterval(() => loadClients($("#searchInput").val()), 3000);

loadClients();
