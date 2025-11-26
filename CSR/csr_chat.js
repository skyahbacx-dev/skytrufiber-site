/* =======================================================
   CSR CHAT — FULL JS WITH ASSIGN LOGIC + UI FIX
======================================================= */

const BASE_MEDIA = "https://f000.backblazeb2.com/file/ahba-chat-media/";

/* =======================================================
   CSR CHAT — FULL FUNCTIONAL JS WITH ASSIGN BUTTONS
======================================================= */

let activeClient = 0;
let filesToSend = [];
let lastMessageCount = 0;

function loadClients(search = "") {
    $.get("client_list.php", { search: search }, function (data) {
        $("#clientList").html(data);
    });
}

$("#searchInput").on("input", function () {
    loadClients($(this).val());
});

/* SELECT CLIENT */
function selectClient(id, name) {
    activeClient = id;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);
    $("#chatMessages").html("");
    lastMessageCount = 0;

    loadMessages(true);
    loadClientInfo();
}

/* ASSIGN & UNASSIGN */
function assignClient(e, id) {
    e.stopPropagation();
    $.post("assign_client.php", { client_id: id }, () => loadClients());
}

function unassignClient(e, id) {
    e.stopPropagation();
    $.post("unassign_client.php", { client_id: id }, () => loadClients());
}

/* LOAD CLIENT INFO */
function loadClientInfo() {
    $.getJSON("client_info.php?id=" + activeClient, data => {
        $("#infoName").text(data.name);
        $("#infoEmail").text(data.email);
        $("#infoDistrict").text(data.district);
        $("#infoBrgy").text(data.barangay);
    });
}

/* LOAD MESSAGES */
function loadMessages(initial = false) {
    if (!activeClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + activeClient, function (messages) {
        if (initial) $("#chatMessages").html("");

        if (messages.length > lastMessageCount) {
            const newMsgs = messages.slice(lastMessageCount);

            newMsgs.forEach(m => {
                const side = (m.sender_type === "csr") ? "csr" : "client";

                $("#chatMessages").append(`
                    <div class="msg-row ${side}">
                        <div class="bubble">${m.message}</div>
                    </div>
                `);
            });

            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }

        lastMessageCount = messages.length;
    });
}

/* SEND MESSAGE */
$("#sendBtn").click(sendMessage);
$("#messageInput").keypress(e => { if (e.key === "Enter") sendMessage(); });

function sendMessage() {
    const msg = $("#messageInput").val().trim();
    if (!msg) return;

    $.post("save_chat_csr.php", { message: msg, client_id: activeClient }, () => {
        $("#messageInput").val("");
        loadMessages(false);
        loadClients();
    });
}

/* RIGHT PANEL TOGGLE */
function toggleClientInfo() {
    $("#infoPanel").toggleClass("show");
}

/* AUTO REFRESH */
setInterval(() => loadClients($("#searchInput").val()), 1500);
setInterval(() => loadMessages(false), 1200);

loadClients();

