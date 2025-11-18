let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];

// SELECT CLIENT
function selectClient(id, name, assigned){
    selectedClient = id;
    assignedTo = assigned;

    $("#chatName").text(name);

    const currentCSR = csrFullname;

    if (!assigned || assigned === currentCSR) {
        $("#messageInput").prop("disabled", false);
        $("#sendBtn").prop("disabled", false);
        $("#fileInput").prop("disabled", false);
        $(".upload-icon").show();
    } else {
        $("#messageInput").prop("disabled", true);
        $("#sendBtn").prop("disabled", true);
        $("#fileInput").prop("disabled", true);
        $(".upload-icon").hide();
    }

    loadClientInfo();
    loadMessages();
}

// ASSIGN
function assignClient(id){
    $.post("assign_client.php", { client_id:id }, loadClients);
}

// UNASSIGN
function unassignClient(id){
    $.post("unassign_client.php", { client_id:id }, loadClients);
}

/******** LOAD MESSAGES ********/
function loadMessages(){
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        messages.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}"><div class="bubble">${m.message || ""}`;

            if (m.media_path){
                if (m.media_type === "image"){
                    html += `<img src="${m.media_path}" class="file-img">`;
                } else {
                    html += `<video controls class="file-img"><source src="${m.media_path}"></video>`;
                }
            }

            html += `<div class="meta">${m.created_at}</div></div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}
