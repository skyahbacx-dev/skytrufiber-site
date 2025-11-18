// csr_chat.js
// STRICT: logic only, no layout changes

let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let shouldScrollToBottom = false; // only scroll when user selects a client

/******** SIDEBAR ********/
function toggleSidebar() {
    const sb = document.querySelector(".sidebar");
    const ov = document.querySelector(".sidebar-overlay");
    if (!sb || !ov) return;

    sb.classList.toggle("open");
    ov.classList.toggle("show");
}

/******** SLIDING CLIENT INFO PANEL ********/
function toggleClientInfo() {
    const p = document.getElementById("clientInfoPanel");
    if (!p) return;
    p.classList.toggle("show");
}

/******** LOAD CLIENT LIST ********/
function loadClients(searchText = "") {
    $.get("client_list.php", { search: searchText }, function (data) {
        $("#clientList").html(data);
    });
}

// live search
$(document).on("keyup", ".search", function () {
    loadClients($(this).val());
});

/******** SELECT CLIENT ********/
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo = assigned || "";

    // Highlight selected client tile
    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatStatus").text(assignedTo ? "Active Chat" : "Unassigned");

    const lockedToOther =
        assignedTo && assignedTo !== csrFullname && assignedTo !== "";

    $("#messageInput").prop("disabled", lockedToOther);
    $("#sendBtn").prop("disabled", lockedToOther);

    if (lockedToOther) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
    }

    shouldScrollToBottom = true; // scroll only right after selecting
    loadClientInfo();
    loadMessages();
}

/******** ASSIGN / UNASSIGN ********/
function assignClient(id) {
    $.post("assign_client.php", { client_id: id }, function () {
        loadClients($(".search").val() || "");
    });
}

function unassignClient(id) {
    if (!confirm("Remove this client from your assignment?")) return;
    $.post("unassign_client.php", { client_id: id }, function () {
        loadClients($(".search").val() || "");
    });
}

/******** LOAD CLIENT INFO ********/
function loadClientInfo() {
    if (!selectedClient) return;
    $.getJSON("client_info.php", { id: selectedClient }, function (info) {
        $("#infoName").text(info.name || "");
        $("#infoEmail").text(info.email || "");
        $("#infoDistrict").text(info.district || "");
        $("#infoBrgy").text(info.barangay || "");
    });
}

/******** LOAD MESSAGES ********/
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php", { client_id: selectedClient }, function (messages) {
        let html = "";

        messages.forEach(m => {
            const side = m.sender_type === "csr" ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">`;

            if (m.message) {
                html += `${m.message}`;
            }

            if (m.media_url) {
                if (m.media_type === "image") {
                    html += `<br><img src="${m.media_url}" class="file-img" onclick="openMedia('${m.media_url}')">`;
                } else if (m.media_type === "video") {
                    html += `<br><video controls class="file-img" onclick="openMedia('${m.media_url}')">
                                <source src="${m.media_url}">
                             </video>`;
                }
            }

            html += `   <div class="meta">${m.created_at || ""}</div>
                        </div>
                      </div>`;
        });

        $("#chatMessages").html(html);

        // ONLY scroll right after user selected / sent, not on every poll
        if (shouldScrollToBottom) {
            const box = $("#chatMessages")[0];
            if (box) box.scrollTop = box.scrollHeight;
            shouldScrollToBottom = false;
        }
    });
}

/******** PREVIEW MULTIPLE ********/
$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-item">
                    ${
                        file.type.includes("video")
                            ? `<video src="${ev.target.result}" muted></video>`
                            : `<img src="${ev.target.result}">`
                    }
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/******** SEND MESSAGE ********/
$("#sendBtn").on("click", function () {
    const msg = $("#messageInput").val().trim();
    if (!selectedClient) return;
    if (!msg && filesToSend.length === 0) return;

    const fd = new FormData();
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
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            shouldScrollToBottom = true; // scroll after send
            loadMessages();
        }
    });
});

/******** MODAL VIEW MEDIA ********/
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").on("click", function () {
    $("#mediaModal").removeClass("show");
});

/******** AUTO REFRESH (NO AUTOSCROLL SPAM) ********/
setInterval(() => loadClients($(".search").val() || ""), 4000);
setInterval(loadMessages, 1500);

// initial load
$(function () {
    loadClients();
});
