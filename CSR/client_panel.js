let currentClient = null;
let canChat = false;

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

function closeInfo(){
    document.getElementById('clientInfoPanel').classList.remove('show');
}

async function loadClients(){
    const res = await fetch('?ajax=load_clients');
    const clients = await res.json();
    const list = document.getElementById('clientList');
    list.innerHTML = '';

    clients.forEach(c=>{
        const dot = c.online ? "🟢" : "⚪";
        list.innerHTML += `
            <div class="client-item" onclick="selectClient(${c.id}, '${c.name.replace(/'/g,"\\'")}')">
                <span>${dot} ${c.name}</span>
            </div>`;
    });
}
loadClients();
setInterval(loadClients, 5000);

async function selectClient(id,name){
    currentClient=id;
    document.getElementById("selected-client").innerText=name;
    loadChat();
    openInfo(id);
}

async function loadChat(){
    if(!currentClient) return;
    const r = await fetch(`?ajax=chat&client_id=${currentClient}`);
    const data = await r.json();
    const m = document.getElementById("messages");
    m.innerHTML = '';
    data.forEach(msg=>{
        m.innerHTML += `
            <div class="message ${msg.sender_type}">
                <div class="bubble">${msg.message}</div>
            </div>`;
    });
    m.scrollTop = m.scrollHeight;
}

async function sendMessage(){
    const msg = document.getElementById('msg').value.trim();
    if(!msg || !canChat) return;
    await fetch('?ajax=send',{
        method:'POST',
        body:new URLSearchParams({client_id:currentClient,msg})
    });
    document.getElementById("msg").value="";
    loadChat();
}

async function openInfo(id){
    const res = await fetch(`?ajax=client_info&id=${id}`);
    const d = await res.json();
    canChat = d.assigned_csr === "<?= $csr_user ?>";
    document.getElementById("client-info-content").innerHTML = `
        <p><b>Name:</b> ${d.name}</p>
        <p><b>District:</b> ${d.district}</p>
        <p><b>Barangay:</b> ${d.barangay}</p>
        <p><b>Email:</b> ${d.email}</p>
        <p><b>Balance:</b> ₱${d.balance}</p>
        <p><b>Assigned CSR:</b> ${d.assigned_csr}</p>
        <button onclick="closeInfo()" style="margin-top:10px;">Close</button>
    `;
    document.getElementById("clientInfoPanel").classList.add("show");
}

setInterval(loadChat,3000);
