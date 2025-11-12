/* CSR Dashboard front-end (tabs + ajax + messenger UI) */

(() => {
  const ajax = (params = {}) => {
    const url = params.url || window.AJAX_URL;
    const opt = params.opt || {};
    return fetch(url + (params.query ? '?' + params.query : ''), opt)
      .then(r => {
        const ct = r.headers.get('content-type') || '';
        if (ct.indexOf('application/json') !== -1) return r.json();
        return r.text();
      });
  };

  // UI elements
  const clientCol = document.getElementById('client-col');
  const messagesBox = document.getElementById('messages');
  const chatAvatar = document.getElementById('chatAvatar');
  const chatName = document.getElementById('chat-name');
  const chatStatus = document.getElementById('chat-status');
  const composer = document.getElementById('composer');
  const msgInput = document.getElementById('msg');
  const sendBtn = document.getElementById('sendBtn');
  const typingIndicator = document.getElementById('typingIndicator');
  const remindersPanel = document.getElementById('reminders');

  let currentTab = 'all';
  let currentClient = 0;
  let currentAssignee = null;

  // Tabs click
  document.querySelectorAll('.tab').forEach(t => {
    t.addEventListener('click', (e) => {
      document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      const tab = t.dataset.tab;
      switchTab(tab);
    });
  });

  function switchTab(tab) {
    currentTab = (tab === 'rem') ? 'rem' : tab;
    if (tab === 'rem') {
      remindersPanel.style.display = 'block';
      document.getElementById('sidebar').style.display = 'none';
      loadReminders();
    } else {
      remindersPanel.style.display = 'none';
      document.getElementById('sidebar').style.display = 'block';
      loadClients();
    }
  }

  // Sidebar toggle
  window.toggleSidebar = (force) => {
    const sb = document.getElementById('sidebar');
    if (force === false) sb.style.display = 'none';
    else sb.style.display = (sb.style.display === 'none' || sb.style.display === '') ? 'block' : 'none';
  };

  // load clients
  function loadClients() {
    clientCol.innerHTML = '<div class="loading">Loading clients…</div>';
    ajax({ query: 'clients=1&tab=' + encodeURIComponent(currentTab) })
      .then(html => {
        // endpoint returns HTML fragment
        if (typeof html === 'string') {
          clientCol.innerHTML = html;
          // attach click handlers
          clientCol.querySelectorAll('.client-item').forEach(el => {
            el.addEventListener('click', () => selectClient(el));
          });
        } else {
          clientCol.innerHTML = '<div class="loading">No clients</div>';
        }
      })
      .catch(err => {
        clientCol.innerHTML = '<div class="loading">Failed to load clients</div>';
        console.error(err);
      });
  }

  // select client
  function selectClient(el) {
    currentClient = parseInt(el.dataset.id, 10) || 0;
    currentAssignee = el.dataset.csr || 'Unassigned';
    const name = el.dataset.name || 'Client';
    chatName.textContent = name;
    composer.style.display = 'flex';
    msgInput.value = '';

    // load profile for avatar
    ajax({ query: 'client_profile=1&name=' + encodeURIComponent(name) })
      .then(profile => {
        setAvatar(name, (profile && profile.gender) ? profile.gender.toLowerCase() : null, profile && profile.avatar);
        chatStatus.textContent = profile && profile.online ? 'Online' : 'Offline';
      });

    loadChat();
  }

  // set avatar
  function setAvatar(name, gender, avatarFile) {
    chatAvatar.innerHTML = '';
    if (avatarFile) {
      const img = document.createElement('img');
      img.src = 'uploads/' + avatarFile;
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.borderRadius = '50%';
      chatAvatar.appendChild(img);
    } else if (gender === 'female') {
      const img = document.createElement('img');
      img.src = 'CSR/penguin.png';
      img.style.width = '100%'; img.style.height = '100%'; img.style.borderRadius = '50%';
      chatAvatar.appendChild(img);
    } else if (gender === 'male') {
      const img = document.createElement('img');
      img.src = 'CSR/lion.png';
      img.style.width = '100%'; img.style.height = '100%'; img.style.borderRadius = '50%';
      chatAvatar.appendChild(img);
    } else {
      // initials
      chatAvatar.textContent = name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
      chatAvatar.style.background = '#ffffff22';
    }
  }

  // load chat messages
  function loadChat() {
    if (!currentClient) return;
    ajax({ query: 'load_chat=1&client_id=' + currentClient })
      .then(rows => {
        if (!Array.isArray(rows)) return;
        messagesBox.innerHTML = '';
        rows.forEach(m => {
          const div = document.createElement('div');
          div.className = 'msg ' + (m.sender === 'csr' ? 'csr' : 'client');
          const bubble = document.createElement('div');
          bubble.className = 'bubble';
          const strong = document.createElement('strong');
          // show CSR name when csr
          if (m.sender === 'csr') strong.textContent = (m.csr_fullname || CSR_FULLNAME || 'CSR') + ': ';
          else strong.textContent = (m.client || 'Client') + ': ';
          bubble.appendChild(strong);
          const tspan = document.createElement('span');
          tspan.innerHTML = ' ' + (m.message || '');
          bubble.appendChild(tspan);

          div.appendChild(bubble);

          const meta = document.createElement('div');
          meta.className = 'meta';
          meta.textContent = m.time || '';
          div.appendChild(meta);

          messagesBox.appendChild(div);
        });
        messagesBox.scrollTop = messagesBox.scrollHeight;
      });
  }

  // send message
  function sendMsg() {
    if (!currentClient) return alert('Select a client first');
    if (currentAssignee && currentAssignee !== 'Unassigned' && currentAssignee !== CSR_USER) {
      return alert('This client is assigned to another CSR. You cannot reply.');
    }
    const txt = msgInput.value.trim();
    if (!txt) return;
    const form = new URLSearchParams();
    form.set('client_id', currentClient);
    form.set('msg', txt);

    ajax({
      query: 'send=1',
      opt: { method: 'POST', body: form }
    }).then(res => {
      msgInput.value = '';
      loadChat();
    });
  }

  // typing handler (notifies server)
  let typingTimer = null;
  function typing() {
    ajax({ query: 'typing=1&client_id=' + currentClient });
    clearTimeout(typingTimer);
    typingIndicator.style.display = 'block';
    typingTimer = setTimeout(() => typingIndicator.style.display = 'none', 1200);
  }

  // reminders
  function loadReminders() {
    const q = document.getElementById('rem-q') ? document.getElementById('rem-q').value : '';
    ajax({ query: 'reminders=1&q=' + encodeURIComponent(q) })
      .then(list => {
        const box = document.getElementById('rem-list');
        box.innerHTML = '';
        if (!Array.isArray(list) || list.length === 0) {
          box.innerHTML = '<div class="card">No reminders</div>';
          return;
        }
        list.forEach(item => {
          const d = document.createElement('div'); d.className = 'card';
          d.innerHTML = `<strong>${item.name}</strong><div>${item.email}</div><div>Due: ${item.due}</div>`;
          box.appendChild(d);
        });
      });
  }

  // assign/unassign actions (delegated)
  window.assignClient = function(id) {
    const body = new URLSearchParams(); body.set('client_id', id);
    ajax({ query:'assign=1', opt:{ method:'POST', body } }).then(r => {
      if (r === 'taken') alert('Already assigned');
      loadClients();
    });
  };
  window.unassignClient = function(id) {
    if(!confirm('Unassign this client?')) return;
    const body = new URLSearchParams(); body.set('client_id', id);
    ajax({ query:'unassign=1', opt:{ method:'POST', body } }).then(()=>loadClients());
  };

  // event listeners
  sendBtn.addEventListener('click', sendMsg);
  msgInput.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') sendMsg();
    else typing();
  });

  // periodic refresh
  setInterval(()=> {
    if (currentClient) loadChat();
    if (remindersPanel.style.display !== 'none') loadReminders();
  }, 5000);

  // initial
  loadClients();
  switchTab('all');

  // expose for debugging
  window._csr_debug = { loadClients, loadChat, switchTab };
})();
