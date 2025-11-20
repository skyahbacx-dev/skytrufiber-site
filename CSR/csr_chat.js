let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let lastMessageCount = 0;

/* ========== SIDEBAR TOGGLE ========== */
function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("show");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/* ========== LOAD CLIENT LIST ========== */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* ========== SELECT CLIENT ========== */
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    const locked = assigned && assigned !== csrFullname;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    $("#fileInput").prop("disabled", locked);

    if (locked) $(".upload-icon").hide();
    else $(".upload-icon").show();

    // auto-close sidebar on mobile
    if (window.innerWidth < 900) {
        document.querySelector(".sidebar").classList.remove("show");
        document.querySelector(".sidebar-overlay").classList.remove("show");
    }

    loadClientInfo();
    loadMessages(true);
}

/* ========== LOAD CLIENT INFO PANEL ========== */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/* ========== LOAD CHAT MESSAGES ========== */
function loadMessages(autoScroll) {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";

        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `
                <div class="msg ${side}">
                    <img src="${side === "csr" ? "upload/default-avatar.png" : "upload/default-avatar.png"}" class="avatar-small">
                    <div>
                        <div class="bubble">${m.message || ""}`;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img" onclick="openMedia('${m.media_path}')">`;
                } else {
                    html += `<video class="file-img" controls onclick="openMedia('${m.media_path}')">
                                <source src="${m.media_path}">
                             </video>`;
                }
            }

            html += `
                        </div>
                        <div class="meta">${m.created_at}</div>
                    </div>
                </div>`;
        });

        $("#chatMessages").html(html);

        // Auto scroll logic
        if (autoScroll || messages.length > lastMessageCount) {
            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/* ========== HANDLE FILE CLICK ========== */
$(".upload-icon").on("click", () => $("#fileInput").click());

/* ========== PREVIEW ATTACHMENTS ========== */
$("#fileInput").on("change", function (e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* ========== SEND MESSAGE ========== */
$("#sendBtn").click(function () {
    let msg = $("#messageInput").val();
    if (!msg && filesToSend.length === 0) return;

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
        success: function () {
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];

            loadMessages(true);
        }
    });
});

/* ========== MEDIA MODAL VIEWER ========== */
function openMedia(src) {
    $("#mediaModal").addClass("show");
    $("#mediaModalContent").attr("src", src);
}
$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

/* ========== AUTO REFRESH ========== */
setInterval(loadClients, 4000);
setInterval(() => loadMessages(false), 1500);

loadClients();

/* ========== SLIDE INFO PANEL ========== */
function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}
