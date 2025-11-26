// ===============================
// CSR CHAT FRONTEND JS (FULL FILE)
// ===============================

let ACTIVE_CLIENT = null;
let lastMessageCount = 0;

// ================= LOADING CLIENT LIST =================
function loadClients() {
    $.get("client_list.php", function (html) {
        $("#clientList").html(html);
    });
}

// ================= LOADING CHAT MESSAGES ===============
function loadChat() {
    if (!ACTIVE_CLIENT) return;

    $.get("load_chat_csr.php", { client_id: ACTIVE_CLIENT }, function (response) {
        try {
            let data = JSON.parse(response);

            $("#chatMessages").html("");

            data.messages.forEach(msg => renderMessage(msg));

            $("#chatName").text(data.client.full_name);
            $("#infoName").text(data.client.full_name);
            $("#infoEmail").text(data.client.email);
            $("#infoDistrict").text(data.client.district);
            $("#infoBrgy").text(data.client.barangay);

            $("#statusDot").removeClass("online offline")
                .addClass(data.client.is_online ? "online" : "offline");
            $("#chatStatus").html(`<span id="statusDot" class="status-dot ${data.client.is_online ? "online" : "offline"}"></span> ${data.client.is_online ? "Online" : "Offline"}`);

            if (data.assigned && data.assigned !== "NONE") {
                $("#assignLabel").text(`Assigned to ${data.assigned}`);
                $("#assignYes").hide();
                $("#assignNo").show();
            } else {
                $("#assignLabel").text("Assign this client?");
                $("#assignYes").show();
                $("#assignNo").hide();
            }

            let newCount = data.messages.length;
            if (newCount > lastMessageCount) {
                $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
            }

            lastMessageCount = newCount;

        } catch (e) {
            console.log("Error loading chat:", e, response);
        }
    });
}

// =============== RENDER UI MESSAGE BUBBLE =================
function renderMessage(m) {
    const wrap = document.createElement("div");
    wrap.className = (m.sender_type === "csr") ? "msg-row msg-out" : "msg-row msg-in";

    const bubble = document.createElement("div");
    bubble.className = "bubble";

    if (m.message && m.message !== "null") {
        bubble.appendChild(document.createTextNode(m.message));
    }

    if (m.media && Array.isArray(m.media)) {
        m.media.forEach(file => {
            if (file.media_type === "image") {
                const img = document.createElement("img");
                img.src = file.media_path;
                img.className = "media-img";
                img.onclick = () => openMedia(img.src);
                bubble.appendChild(img);

            } else if (file.media_type === "video") {
                const video = document.createElement("video");
                video.src = file.media_path;
                video.controls = true;
                video.className = "media-video";
                bubble.appendChild(video);
            }
        });
    }

    const time = document.createElement("div");
    time.className = "time";

    let status = "";
    if (m.sender_type === "csr") {
        status = m.seen ? " <span class='seen-ic'>Seen ✓✓</span>" :
            m.delivered ? " <span class='delivered-ic'>Delivered ✓</span>" : "";
    }

    time.innerHTML = `${m.created_at}${status}`;
    bubble.appendChild(time);

    wrap.appendChild(bubble);
    document.getElementById("chatMessages").appendChild(wrap);
}

// =================== SENDING MESSAGE ===================
$("#sendBtn").click(() => sendMessage());
$("#messageInput").keydown(e => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

function sendMessage() {
    if (!ACTIVE_CLIENT) return;

    const msg = $("#messageInput").val().trim();
    const files = $("#fileInput")[0].files;

    if (!msg && files.length === 0) return;

    const form = new FormData();
    form.append("client_id", ACTIVE_CLIENT);
    form.append("message", msg);

    for (let f of files) form.append("media[]", f);

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: form,
        processData: false,
        contentType: false,
        success: function () {
            $("#messageInput").val("");
            $("#fileInput").val("");
            loadChat();
        }
    });
}

// ==================== MEDIA VIEWING =====================
function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").show();
}
$("#closeMediaModal").click(() => $("#mediaModal").hide());

// ====================== ASSIGN ==========================
$("#assignYes").click(() => {
    $.post("assign_csr.php", { client_id: ACTIVE_CLIENT }, loadChat);
});

$("#assignNo").click(() => {
    $.post("unassign_csr.php", { client_id: ACTIVE_CLIENT }, loadChat);
});

// ==================== SELECT CLIENT LIST ================
$(document).on("click", ".client-item", function () {
    ACTIVE_CLIENT = $(this).data("id");
    lastMessageCount = 0;
    loadChat();
});

// =================== POLLING EVERY SECOND ===============
setInterval(loadChat, 1000);
loadClients();
