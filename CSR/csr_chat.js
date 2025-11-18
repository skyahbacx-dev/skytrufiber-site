let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];

function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo = assigned;

    $("#chatName").text(name);
    $("#messageInput").prop("disabled", assigned && assigned !== csrFullname);
    $("#sendBtn").prop("disabled", assigned && assigned !== csrFullname);

    if (assigned && assigned !== csrFullname) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
    }

    loadClientInfo();
    loadMessages();
}

/******** LOAD MESSAGES *********/
function loadMessages() {
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";

        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">${m.message || ""}`;

            if (m.media_path) {
                if (m.media_type === "image") {
                    html += `<img src="${m.media_path}" class="file-img">`;
                } else {
                    html += `<video class="file-img" controls>
                                <source src="${m.media_path}">
                             </video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div>
                     </div>
                  </div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/******** PREVIEW FILES *********/
$("#fileInput").on("change", function(e){
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = e => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                        ? `<video src="${e.target.result}" muted></video>`
                        : `<img src="${e.target.result}">`}
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/******** SEND **********/
$("#sendBtn").click(function(){
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
        processData:false,
        contentType:false,
        success: function(){
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});
// MEDIA MODAL VIEW HANDLER
$(document).on("click", ".file-img", function(){
    let src = $(this).attr("src") || $(this).find("source").attr("src");
    $("#mediaModalContent").attr("src", src);
    $("#mediaModal").addClass("show");
});

$(document).on("click", "#closeMediaModal", function(){
    $("#mediaModal").removeClass("show");
});

setInterval(loadMessages, 1500);
