let selectedClient = 0;
let filesToSend = [];

function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("open");

    const overlay = document.querySelector(".sidebar-overlay");
    overlay.classList.toggle("show");

    // Disable scrolling when sidebar is open
    if (overlay.classList.contains("show")) {
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = "auto";
    }
}


function toggleClientInfo() {
    document.getElementById("clientInfoPanel").classList.toggle("show");
}


/* Load clients list */
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });
}

/* Load Messages */
function loadMessages() {
    if (!selectedClient) return;
    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";
            html += `<div class="msg ${side}"><div class="bubble">${m.message || ""}</div>`;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img" style="max-width:250px;border-radius:12px;">`;
                } else {
                    html += `<video controls class="file-video" style="max-width:250px;border-radius:12px;">
                                <source src="${m.media_path}">
                            </video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/***** FILE SELECTION & PREVIEW *****/
$("#fileInput").on("change", function(e) {
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = function(e) {
            $("#previewArea").append(`
                <div class="preview-item">
                    ${file.type.includes("video") ? `<video src="${e.target.result}" muted></video>` :
                    `<img src="${e.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/* SEND */
$("#sendBtn").click(function(){
    let msg = $("#messageInput").val();
    if (!msg && filesToSend.length === 0) return;

    let formData = new FormData();
    formData.append("message", msg);
    formData.append("client_id", selectedClient);
    formData.append("csr_fullname", "<?php echo $csrFullName; ?>");

    filesToSend.forEach(f => formData.append("files[]", f));

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function() {
            $("#messageInput").val("");
            $("#previewArea").html("");
            filesToSend = [];
            loadMessages();
        }
    });
});
function checkStatus() {
    if (!selectedClient) return;

    $.get("check_status.php?id=" + selectedClient, function(res){
        if(res.status === "online"){
            $("#statusDot").removeClass("offline").addClass("online");
        } else {
            $("#statusDot").removeClass("online").addClass("offline");
        }
    });
}

setInterval(checkStatus, 3000);

setInterval(loadMessages, 1800);
loadClients();
