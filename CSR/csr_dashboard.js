/* ===========================
   GLOBAL VARS
=========================== */
let currentTab = "all";
let currentClient = 0;
let currentAssignee = "";
let typingTimer;

const csr = window.CSRUser;

/* ===========================
   SIDEBAR
=========================== */
function toggleSidebar() {
    const sb = document.getElementById("sidebar");
    const ov = document.getElementById("sidebar-overlay");

    if (sb.classList.contains("active")) {
        sb.classList.remove("active");
        ov.style.display = "none";
    } else {
        sb.classList.add("active");
        ov.style.display = "block";
    }
}

/* ===========================
   CHAT COLLAPSE / EXPAND
=========================== */
function collapseChat() {
    const col = document.getElementById("chat-col");
    const btn = document.getElementById("collapseBtn");

    if (col.classList.contains("collapsed")) {
        col.classList.remove("collapsed");
        btn.textContent = "●";
    } else {
        col.classList.add("collapsed");
        btn.textContent = "i";
    }
}

/* ===========================
   LOAD CLIENTS
=========================== */
function loadClients() {
    fetch(`csr_dashboard_ajax.php?clients=1&tab=${currentTab}`)
        .then(res => res.text())
        .then(html => {
            const box = document.getElementById("client-col");
            box.innerHTML = html;

            document.querySelectorAll(".client-item").forEach(el => {
                el.addEventListener("click", () => selectClient(el));
            });
        });
}

/* ===========================
   SELECT CLIENT
=========================== */
function selectClient(el) {
    currentClient = el.dataset.id;
    currentAssignee = el.dataset.csr;
    const name = el.dataset.name;

    document.getElementById("chat-name").textContent = name;
    document.getElementById("input").style.display = "flex";

    // Load profile
    fetch(`csr_dashboard_ajax.php?client_profile=1&name=${encodeURIComponent(name)}`)
        .then(res => res.json())
        .then(p => {
            const avatarBox = document.getElementById("chatAvatar");
            avatarBox.innerHTML = "";

            if (p.avatar) {
                let img = document.createElement("img");
                img.src = "../" + p.avatar;
                avatarBox.appendChild(img);
            } else if (p.gender === "male") {
                avatarBox.innerHTML = `<img src="../lion.png">`;
            } else if (p.gender === "female") {
                avatarBox.innerHTML = `<img src="../penguin.png">`;
            } else {
                avatarBox.textContent = name.split(" ").map(x=>x[0]).join("");
            }
        });

    loadChat();
}

/* ===========================
   LOAD CHAT
=========================== */
function loadChat() {
    if (!currentClient) return;

    fetch(`csr_dashboard_ajax.php?load_chat=1&client_id=${currentClient}`)
    .then(res => res.json())
    .then(rows => {
        const box = document.getElementById("messages");
        box.innerHTML = "";

        rows.forEach(m => {
            const cls = m.sender === "csr" ? "csr" : "client";
            box.innerHTML += `
                <div class="msg ${cls}">
                    <div class="bubble">
                        <strong>${cls === "csr" ? "CSR " + csr + ": " : ""}</strong> 
                        ${m.message}
                    </div>
                    <div class="meta">${m.time}</div>
                </div>
            `;
        });

        box.scrollTop = box.scrollHeight;
    });
}

/* ===========================
   SEND MESSAGE
=========================== */
function sendMsg() {
    if (!currentClient) return;

    if (currentAssignee !== "Unassigned" && currentAssignee !== csr) {
        alert("❌ You cannot reply — this client is owned by another CSR.");
        return;
    }

    const msgEl = document.getElementById("msg");
    const msg = msgEl.value.trim();
    if (!msg) return;

    fetch("csr_dashboard_ajax.php?send=1", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `client_id=${currentClient}&msg=${encodeURIComponent(msg)}`
    }).then(() => {
        msgEl.value = "";
        loadChat();
    });
}

/* ===========================
   TYPING INDICATOR
=========================== */
function typing() {
    clearTimeout(typingTimer);
    document.getElementById("typingIndicator").style.display = "block";

    typingTimer = setTimeout(() => {
        document.getElementById("typingIndicator").style.display = "none";
    }, 1200);
}

/* ===========================
   ASSIGN / UNASSIGN
=========================== */
function assignClient(id) {
    fetch("csr_dashboard_ajax.php?assign=1", {
        method: "POST",
        body: `client_id=${id}`,
        headers: {"Content-Type":"application/x-www-form-urlencoded"}
    }).then(() => loadClients());
}

function unassignClient(id) {
    if (!confirm("Unassign this client?")) return;
    fetch("csr_dashboard_ajax.php?unassign=1", {
        method: "POST",
        body: `client_id=${id}`,
        headers: {"Content-Type":"application/x-www-form-urlencoded"}
    }).then(() => loadClients());
}

/* ===========================
   REMINDERS
=========================== */
function loadReminders() {
    const q = document.getElementById("rem-q").value;

    fetch(`csr_dashboard_ajax.php?reminders=1&q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(list => {
            const box = document.getElementById("rem-list");
            box.innerHTML = "";

            list.forEach(item => {
                box.innerHTML += `
                    <div class="card">
                        <strong>${item.name}</strong><br>
                        <small>${item.email}</small><br>
                        <div>Due: ${item.due}</div>
                        <div>${item.badges}</div>
                    </div>
                `;
            });
        });
}

/* ===========================
   TAB SWITCHING
=========================== */
function switchTab(tab) {
    document.querySelectorAll(".tab").forEach(el => el.classList.remove("active"));
    document.getElementById(`tab-${tab}`).classList.add("active");

    if (tab === "rem") {
        currentTab = "all";
        document.getElementById("messages").style.display = "none";
        document.getElementById("input").style.display = "none";
        document.getElementById("chat-head").style.display = "none";
        document.getElementById("reminders").style.display = "block";
        loadReminders();
        return;
    }

    document.getElementById("reminders").style.display = "none";
    document.getElementById("messages").style.display = "block";
    document.getElementById("chat-head").style.display = "flex";

    currentTab = tab;
    loadClients();
}

/* ===========================
   AUTO REFRESH
=========================== */
setInterval(() => {
    if (currentClient) loadChat();
    if (document.getElementById("reminders").style.display !== "none") loadReminders();
}, 4000);

/* ===========================
   INIT
=========================== */
loadClients();
