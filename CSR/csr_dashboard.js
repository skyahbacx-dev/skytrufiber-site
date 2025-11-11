let currentClient = null;

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    if (document.getElementById('tab-' + tab))
        document.getElementById('tab-' + tab).classList.add('active');

    if (tab === 'rem') {
        document.getElementById('reminders').style.display = 'block';
        document.getElementById('messages').style.display = 'none';
        document.getElementById('input').style.display = 'none';
    } else {
        document.getElementById('reminders').style.display = 'none';
        document.getElementById('messages').style.display = 'block';
        document.getElementById('input').style.display = currentClient ? 'flex' : 'none';
        loadClients(tab);
    }
}

function loadClients(tab) {
    fetch('csr_dashboard_ajax.php?clients=1&tab=' + tab)
    .then(res => res.text())
    .then(html => {
        document.getElementById('client-col').innerHTML = html;
    });
}

function openChat(id) {
    currentClient = id;
    document.getElementById('input').style.display = 'flex';

    fetch('csr_dashboard_ajax.php?load_chat=1&client_id=' + id)
    .then(r => r.json())
    .then(data => {
        let box = document.getElementById('messages');
        box.innerHTML = "";

        data.forEach(m => {
            let div = document.createElement('div');
            div.className = m.sender === 'csr' ? 'msg right' : 'msg left';
            div.innerHTML =
                `<div class="bubble">${m.message}</div>
                <div class="time">${m.time}</div>`;
            box.appendChild(div);
        });
        box.scrollTop = box.scrollHeight;
    });
}

function sendMsg() {
    let txt = document.getElementById('msg');
    if (!txt.value.trim()) return;

    fetch('csr_dashboard_ajax.php?send=1', {
        method: 'POST',
        body: new URLSearchParams({
            client_id: currentClient,
            msg: txt.value
        })
    }).then(() => {
        txt.value = "";
        openChat(currentClient);
    });
}

function loadReminders() {
    let q = document.getElementById('rem-q').value;

    fetch('csr_dashboard_ajax.php?reminders=1&q=' + q)
    .then(r => r.json())
    .then(list => {
        let out = "";
        list.forEach(r => {
            out += `
            <div class="rem-item">
                <div>${r.name}</div>
                <div>${r.email}</div>
                <div>${r.due}</div>
            </div>`;
        });
        document.getElementById('rem-list').innerHTML = out;
    });
}
