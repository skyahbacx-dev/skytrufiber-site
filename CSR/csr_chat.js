/* =======================================================
   CSR CHAT — FINAL JS (MATCHES chat.php + chat.css)
   ======================================================= */

let activeClient = 0;          // users.id
let activeClientName = "";
let filesToSend = [];
let lastMessageCount = 0;
let loadingMessages = false;

/* ========== LOAD CLIENT LIST ========== */
function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (html) {
        $("#clientList").html(html);

        // keep highlight if active client still exists
        if (activeClient) {
            $("#client-" + activeClient).addClass("active-client");
        }
    });
}

/* ========== SELECT CLIENT FROM LIST ========== */
function selectClient(id, name) {
    activeClient = id;
    activeClientName = name;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    // reset messages & counters
    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* ========== LOAD CLIENT INFO (RIGHT PANEL) ========== */
function loadClientInfo() {
    if (!activeClient) return;

    $.getJSON("client_info.php", { id: activeClient }, function (data) {
        $("#infoName").text(data.name || "");
        $("#infoEmail").text(data.email || "");
        $("#infoDistrict").text(data.district || "");
        $("#infoBrgy").text(data.barangay || "");

        // assign/unassign logic
        const assignedTo = data.assigned_csr || null;
        const currentCSR = data.current_csr || null; // optional, if you send it

        let label = "";
        let showYes = false;
        let showNo = false;

        if (!assignedTo) {
            label = "Assign this client to you?";
            showYes = showNo = true;
        } else if (assignedTo === currentCSR) {
            label = "This client is assigned to you. Remove?";
            showYes = showNo = true;
        } else {
            label = "Assigned to " + assignedTo + ". You cannot modify.";
            showYes = showNo = false;
        }

        $("#assignLabel").text(label);
        $("#assignYes").toggle(showYes);
        $("#assignNo").toggle(showNo);
    });
}

/* ========== DATE LABEL (Today / date) ========== */
function dateLabel(dateString) {
    const d = new Date(dateString);
    const now = new Date();

    const dStr = d.toDateString();
    const nowStr = now.toDateString();

    if (dStr === nowStr) return "Today";
    return d.toLocaleDateString();
}

/* ========== LOAD CHAT MESSAGES ========== */
function loadMessages(initial = false) {
    if (!activeClient || loadingMessages) return;
    loadingMessages = true;

    $.getJSON("load_chat_csr.php", { client_id: activeClient }, function (messages) {
        if (initial) {
            $("#chatMessages").html("");
            lastMessageCount = 0;
        }

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);
            let lastDateLabel = null;

            newMsgs.forEach(m => {
                const msgDateLabel = dateLabel(m.raw_created_at || m.created_at);

                if (msgDateLabel !== lastDateLabel) {
                    $("#chatMessages").append(
                        `<div class="date-separator">${msgDateLabel}</div>`
                    );
                    lastDateLabel = msgDateLabel;
                }

                const side = (m.sender_type === "csr") ? "csr" : "client";

                // attachment (single or array)
                let attachmentHTML = "";
                if (m.media && Array.isArray(m.media)) {
                    m.media.forEach(file => {
                        if (file.media_type === "image") {
                            attachmentHTML += `<img src="${file.media_path}" class="file-img" onclick="openMedia('${file.media_path}')">`;
                        } else if (file.media_type === "video") {
                            attachmentHTML += `
                                <video class="file-img" controls>
                                  <source src="${file.media_path}">
                                </video>`;
                        }
                    });
                } else if (m.media_path) {
                    // fallback single
                    if (m.media_type === "image") {
                        attachmentHTML = `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                    } else if (m.media_type === "video") {
                        attachmentHTML = `
                            <video class="file-img" controls>
                              <source src="${m.media_path}">
                            </video>`;
                    }
                }

                // ticks for CSR messages
                let statusHTML = "";
                if (m.sender_type === "csr") {
                    if (m.seen) {
                        statusHTML = `<span class="tick blue">✓✓</span>`;
                    } else if (m.delivered) {
                        statusHTML = `<span class="tick">✓✓</span>`;
                    }
                }

                const safeMsg = m.message ? $('<div>').text(m.message).html() : "";

                const html = `
                    <div class="msg-row ${side}">
                        <div class="bubble-wrapper">
                            <div class="bubble">${safeMsg}${attachmentHTML}</div>
                            <div class="meta">${m.created_at || ""} ${statusHTML}</div>
                        </div>
                    </div>
                `;
                $("#chatMessages").append(html);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
        loadingMessages = false;
    });
}

/* ========== SEND MESSAGE ========== */

$("#sendBtn").on("click", sendMessage);
$("#messageInput").on("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

function sendMessage() {
    if (!activeClient) return;

    const msg = $("#messageInput").val().trim();
    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
    fd.append("client_id", activeClient);
    fd.append("message", msg);

    filesToSend.forEach(file => {
        fd.append("media[]", file);
    });

    $.ajax({
        url: "save_chat_csr.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function () {
            $("#messageInput").val("");
            $("#fileInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            // refresh
            loadMessages(false);
            loadClients($("#searchInput").val());
        }
    });
}

/* ========== FILE PREVIEW ========== */

$("#fileInput").on("change", function (e) {
    filesToSend = Array.from(e.target.files || []);
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = function (ev) {
            const isVideo = file.type.startsWith("video/");
            const content = isVideo
                ? `<video src="${ev.target.result}" muted></video>`
                : `<img src="${ev.target.result}">`;

            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${content}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* ========== INFO PANEL TOGGLE ========== */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* ========== ASSIGN / UNASSIGN HANDLERS ========== */

$("#assignYes").on("click", function () {
    if (!activeClient) return;
    $.post("assign_client.php", { user_id: activeClient }, function () {
        loadClients($("#searchInput").val());
        loadClientInfo();
    });
});

$("#assignNo").on("click", function () {
    if (!activeClient) return;
    $.post("unassign_client.php", { user_id: activeClient }, function () {
        loadClients($("#searchInput").val());
        loadClientInfo();
    });
});

/* ========== MEDIA VIEWER ========== */

function openMedia(src) {
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
}

$("#closeMediaModal").on("click", function () {
    $("#mediaModal").removeClass("show");
});

/* ========== SEARCH INPUT BINDING ========== */

$("#searchInput").on("input", function () {
    loadClients(this.value);
});

/* ========== AUTO REFRESH ========== */

setInterval(function () {
    loadMessages(false);
}, 1500);

setInterval(function () {
    loadClients($("#searchInput").val());
}, 3000);

/* INITIAL LOAD */
$(function () {
    loadClients();
});
