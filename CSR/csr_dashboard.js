let currentTab = "all";
let currentClient = 0;
let currentAssignee = "";
let me = ""; // Loaded later

/* SIDEBAR */
function toggleSidebar(show) {
    const s = document.getElementById("sidebar");
    const o = document.getElementById("sidebar-overlay");

    if (show) {
        s.classList.add("active");
        o.style.display = "block";
    } else {
        s.classList.remove("active");
        o.style.display = "none";
    }
}

/* SWITCH TAB */
function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll(".tabs button").forEach(b => b.classList.remove("active"));
    document.getElementById("tab-" + tab).classList.add("active");

    loadClients();
}

/* LOAD CLIENTS */
function loadClients() {
    fetch("csr_ajax.php?action=clients&tab=" + currentTab)
        .then(r => r.json())
        .then(list => {
            const box = document.getElementById("client-col");
            box.innerHTML = "";

            list.forEach(c => {
                const lock = (c.assigned_csr && c.assigned_csr !== me);

                box.innerHTML += `
                    <div class="client-item" onclick="selectClient(${c.id}, '${c.name}', '${c.assigned_csr}')">
                        <div class="client-name">${c.name}</div>
                        <div class="client-email">${c.email ?? ''}</div>
                        <div class="client-assign">Assigned: ${c.assigned_csr ?? 'Unassigned'}</div>
                        <div class="lock">${lock ? "🔒" : ""}</div>
                    </div>
                `;
            });
        });
}

/* SELECT CLIENT */
function selectClient(id, name, assigned) {
    currentClient = id;
    currentAssignee = assigned;

    document.getElementById("chat-title").textContent = name;
    document.getElementById("input").style.display = "flex";

    loadChat();
}

/* LOAD CHAT */
function loadChat() {
    if (!currentClient) return;

    fetch("csr_ajax.php?action=load_chat&client_id=" + currentClient)
        .then(r => r.json())
        .then(msgs => {
            const box = document.getElementById("messages");
            box.innerHTML = "";

            msgs.forEach(m => {
                const sender = (m.sender_type === "csr") ? m.csr_fullname : m.client_name;

                box.innerHTML += `
                <div class="msg ${m.sender_type}">
                    <div class="bubble">
                        <strong>${sender}:</strong> ${m.message}
                    </div>
                    <div class="meta">${new Date(m.created_at).toLocaleString()}</div>
                </div>
                `;
            });

            box.scrollTop = box.scrollHeight;
        });
}

/* SEND MESSAGE */
function sendMsg() {
    if (!currentClient) return;

    if (currentAssignee && currentAssignee !== me) {
        alert("This client is assigned to another CSR.");
        return;
    }

    const msg = document.getElementById("msg").value.trim();
    if (!msg) return;

    fetch("csr_ajax.php?action=send", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "client_id=" + currentClient + "&message=" + encodeURIComponent(msg)
    }).then(() => {
        document.getElementById("msg").value = "";
        loadChat();
    });
}

/* AUTO REFRESH */
setInterval(() => {
    if (currentClient) loadChat();
}, 1500);

/* INITIAL LOAD */
loadClients();
