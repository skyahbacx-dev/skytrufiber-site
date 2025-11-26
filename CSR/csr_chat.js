/* CSR CHAT FINAL JS */

let activeClient = 0;
let filesToSend = [];
let lastCount = 0;

function loadClients() {
    $.get("client_list.php", data => $("#clientList").html(data));
}

function selectClient(id, name) {
    activeClient = id;
    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastCount = 0;
    loadMessages();
    loadClientInfo(id);
}

function loadClientInfo(id) {
    $.getJSON("client_info.php?id=" + id, d => {
        $("#infoName").text(d.full_name);
        $("#infoEmail").text(d.email);
        $("#infoDistrict").text(d.district);
        $("#infoBrgy").text(d.barangay);

        $("#infoPanel").removeClass("locked");
        if (d.assigned_csr && d.assigned_csr !== d.current_csr) {
            $("#assignLabel").text("Assigned to: " + d.assigned_csr);
            $("#assignBtn").hide(); $("#unassignBtn").hide();
        } else if (d.assigned_csr === d.current_csr) {
            $("#assignLabel").text("Assigned to you");
            $("#assignBtn").hide(); $("#unassignBtn").show();
        } else {
            $("#assignLabel").text("Assign this client?");
            $("#assignBtn").show(); $("#unassignBtn").hide();
        }

        $("#assignBtn").off().click(() => assignClient());
        $("#unassignBtn").off().click(() => unassignClient());
    });
}

function assignClient() {
    $.post("assign_client.php", { client_id: activeClient }, () => {
        loadClients();
        loadClientInfo(activeClient);
    });
}

function unassignClient() {
    $.post("unassign_client.php", { client_id: activeClient }, () => {
        loadClients();
        loadClientInfo(activeClient);
    });
}

function loadMessages() {
    if (!activeClient) return;
    $.getJSON(`load_chat_csr.php?client_id=${activeClient}`, msgs => {
        if (msgs.length > lastCount) {
            msgs.slice(lastCount).forEach(m => {
                let side = (m.sender_type === "csr") ? "csr" : "client";
                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div>
                            <div class="bubble">${m.message || ""}</div>
                            <div class="meta">${m.created_at}</div>
                        </div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
            lastCount = msgs.length;
        }
    });
}

$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg) return;

    $.post("save_chat_csr.php", { message: msg, client_id: activeClient }, () => {
        $("#messageInput").val("");
        loadMessages();
        loadClients();
    });
}

setInterval(loadMessages, 1200);
setInterval(loadClients, 2000);

loadClients();
