<?php
/**
 * CSR Dashboard — Messenger-style
 * Mapping: clients.name ↔ users.full_name
 * Auto-detect gender + auto-assign avatar and persist to users table.
 */

session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}
$csr_user = $_SESSION['csr_user'];

/* Load CSR profile */
$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$st->execute([':u' => $csr_user]);
$csr = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

/* Logo */
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ------------------------------ HELPERS ------------------------------ */

function safe($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
}

/**
 * Very lightweight gender guesser based on name endings & given names.
 * Returns 'male' | 'female' | '' (unknown)
 */
function guess_gender_from_name($fullName) {
    $n = strtolower(trim($fullName ?? ''));
    if ($n === '') return '';

    // Extract first token only
    $first = preg_split('/\s+/', $n)[0];

    // Common female endings / names
    $female_endings = ['a','ah','ina','ine','ene','ette','elle','lyn','lynne','anna','anne','ika','ika','isha','esha','ika','ine','ria','riah','maria','liza','ella','ella'];
    foreach ($female_endings as $end) {
        if (str_ends_with($first, $end)) return 'female';
    }

    $female_names = [
        'maria','marie','anna','anne','anne','ally','alyssa','alyssa','kristine','christine','alicia','alicia','ella','ella',
        'sophia','sofia','isabella','isabelle','amelia','olivia','ava','mia','emily','madison','chloe','grace','victoria'
    ];
    if (in_array($first, $female_names, true)) return 'female';

    // Common male endings / names
    $male_names = [
        'john','jhon','jon','michael','mike','mikee','jose','peter','paul','mark','marc','james','josh','joshua','miguel',
        'carlos','carl','jason','allan','alan','aaron','aarontan','waldo','boss','dey','alex','leo','leon','luke','liam'
    ];
    if (in_array($first, $male_names, true)) return 'male';

    // Heuristic: names ending NOT in 'a' skew male
    if (!str_ends_with($first, 'a')) return 'male';

    return '';
}

/**
 * Ensures users row has gender + avatar; will update if missing.
 * Avatar: ../penguin.png for female, ../lion.png for male
 * Returns array: ['email'=>..., 'gender'=>..., 'avatar'=>...]
 */
function ensure_profile($conn, $fullName) {
    // read
    $ps = $conn->prepare("SELECT email, gender, avatar FROM users WHERE full_name = :n LIMIT 1");
    $ps->execute([':n' => $fullName]);
    $u = $ps->fetch(PDO::FETCH_ASSOC) ?: ['email' => null, 'gender' => null, 'avatar' => null];

    $gender = $u['gender'] ?? '';
    $avatar = $u['avatar'] ?? '';

    if (!$gender || !$avatar) {
        // Guess if needed
        if (!$gender) {
            $gender = guess_gender_from_name($fullName);
        }
        if (!$avatar) {
            if ($gender === 'female') $avatar = '../penguin.png';
            elseif ($gender === 'male') $avatar = '../lion.png';
        }

        // Persist if anything is determined
        if ($gender || $avatar) {
            $up = $conn->prepare("
                UPDATE users
                SET gender = COALESCE(:g, gender),
                    avatar = COALESCE(:a, avatar)
                WHERE full_name = :n
            ");
            $up->execute([
                ':g' => $gender ?: null,
                ':a' => $avatar ?: null,
                ':n' => $fullName
            ]);
        }
    }

    return [
        'email'  => $u['email'] ?? '',
        'gender' => $gender ?: '',
        'avatar' => $avatar ?: ''
    ];
}

/* ------------------------------ AJAX ------------------------------ */

if (isset($_GET['ajax'])) {

    /* Load clients list (All / Mine) */
    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';
        $where = ($tab === 'mine') ? " WHERE c.assigned_csr = :csr " : "";

        $q = $conn->prepare("
            SELECT c.id, c.name, c.assigned_csr,
                   (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email
            FROM clients c
            $where
            ORDER BY c.name ASC
        ");
        if ($tab === 'mine') $q->execute([':csr' => $csr_user]); else $q->execute();

        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $assigned = $row['assigned_csr'] ?: 'Unassigned';
            $owned = ($assigned === $csr_user);

            if ($assigned === 'Unassigned') {
                $btn = "<button class='pill green' onclick='assignClient({$row['id']})' title='Assign to me'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='pill red' onclick='unassignClient({$row['id']})' title='Unassign'>−</button>";
            } else {
                $btn = "<button class='pill gray' disabled title='Assigned to another CSR'>🔒</button>";
            }

            echo "
              <div class='client-item' data-id='".safe($row['id'])."' data-name='".safe($row['name'])."' data-csr='".safe($assigned)."'>
                <div class='client-meta'>
                    <div class='client-name'>".safe($row['name'])."</div>
                    ".($row['email'] ? "<div class='client-email'>".safe($row['email'])."</div>" : "")."
                    <div class='client-assign'>Assigned: ".safe($assigned)."</div>
                </div>
                <div class='client-actions'>{$btn}</div>
              </div>
            ";
        }
        exit;
    }

    /* Load messages */
    if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];
        $c = $conn->prepare("
            SELECT ch.message, ch.sender_type, ch.created_at,
                   ch.csr_fullname, ch.assigned_csr,
                   c.name AS client_name
            FROM chat ch
            JOIN clients c ON c.id = ch.client_id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $c->execute([':cid' => $cid]);

        $rows = [];
        while ($r = $c->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'message'      => $r['message'],
                'sender_type'  => $r['sender_type'],
                'time'         => $r['created_at'],
                'client_name'  => $r['client_name'],
                'csr_fullname' => $r['csr_fullname'],
                'assigned_csr' => $r['assigned_csr']
            ];
        }
        echo json_encode($rows); exit;
    }

    /* Ensure / Read client profile (by full name), persist gender & avatar if missing */
    if ($_GET['ajax'] === 'client_profile' && isset($_GET['name'])) {
        $fullName = trim($_GET['name'] ?? '');
        $profile = ensure_profile($conn, $fullName); // updates if missing
        echo json_encode($profile); exit;
    }

    /* Assign client */
    if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
        $cid = (int)$_POST['client_id'];

        $chk = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :i");
        $chk->execute([':i' => $cid]);
        $cur = $chk->fetch(PDO::FETCH_ASSOC);

        if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned') {
            echo 'taken'; exit;
        }

        $u = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :i");
        $u->execute([':c' => $csr_user, ':i' => $cid]);
        echo 'ok'; exit;
    }

    /* Unassign client (only if mine) */
    if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
        $cid = (int)$_POST['client_id'];
        $u = $conn->prepare("
            UPDATE clients
            SET assigned_csr = 'Unassigned'
            WHERE id = :i AND assigned_csr = :c
        ");
        $u->execute([':i' => $cid, ':c' => $csr_user]);
        echo 'ok'; exit;
    }

    /* Reminders from users.date_installed */
    if ($_GET['ajax'] === 'reminders') {
        $needle = strtolower(trim($_GET['q'] ?? ''));
        $rows = [];

        $u = $conn->query("
            SELECT id, full_name, email, date_installed
            FROM users
            WHERE email IS NOT NULL
            ORDER BY full_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $today = new DateTime('today');

        foreach ($u as $usr) {
            if (!$usr['date_installed']) continue;

            $di = new DateTime($usr['date_installed']);
            $dueDay = (int)$di->format('d');

            $base = new DateTime('first day of this month');
            $due  = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
            if ((int)$today->format('d') > (int)$due->format('d')) {
                $base->modify('first day of next month');
                $due  = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
            }

            $oneWeek  = (clone $due)->modify('-7 days');
            $threeDay = (clone $due)->modify('-3 days');

            $cycle = $due->format('Y-m-d');
            $st = $conn->prepare("SELECT reminder_type, status FROM reminders WHERE client_id = :cid AND cycle_date = :cy");
            $st->execute([':cid' => $usr['id'], ':cy' => $cycle]);
            $sent = [];
            foreach ($st as $x) { $sent[$x['reminder_type']] = $x['status']; }

            $badges = [];
            if ($today <= $oneWeek && $today->diff($oneWeek)->days <= 7) {
                $badges[] = ['type'=>'1_WEEK', 'status'=>($sent['1_WEEK'] ?? 'upcoming'), 'date'=>$oneWeek->format('Y-m-d')];
            } elseif ($today == $oneWeek) {
                $badges[] = ['type'=>'1_WEEK', 'status'=>($sent['1_WEEK'] ?? 'due'), 'date'=>$oneWeek->format('Y-m-d')];
            }
            if ($today <= $threeDay && $today->diff($threeDay)->days <= 7) {
                $badges[] = ['type'=>'3_DAYS', 'status'=>($sent['3_DAYS'] ?? 'upcoming'), 'date'=>$threeDay->format('Y-m-d')];
            } elseif ($today == $threeDay) {
                $badges[] = ['type'=>'3_DAYS', 'status'=>($sent['3_DAYS'] ?? 'due'), 'date'=>$threeDay->format('Y-m-d')];
            }

            if (!$badges) continue;

            if ($needle) {
                $hay = strtolower(($usr['full_name'] ?? '').' '.($usr['email'] ?? ''));
                if (strpos($hay, $needle) === false) continue;
            }

            $rows[] = [
                'name'    => $usr['full_name'],
                'email'   => $usr['email'],
                'due'     => $due->format('Y-m-d'),
                'banners' => $badges
            ];
        }

        echo json_encode($rows); exit;
    }

    echo 'invalid'; exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= safe($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
</head>
<body>

<!-- SIDEBAR -->
<div id="sidebar-overlay" onclick="toggleSidebar(false)"></div>
<div id="sidebar">
  <h2>CSR Menu</h2>
  <a onclick="switchTab('all')">💬 Chat Dashboard</a>
  <a onclick="switchTab('mine')">👤 My Clients</a>
  <a onclick="switchTab('rem')">⏰ Reminders</a>
  <a href="survey_responses.php">📝 Survey Responses</a>
  <a href="update_profile.php">👤 Edit Profile</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- HEADER (centered brand like Messenger) -->
<header class="csr-header">
  <button class="hamburger" onclick="toggleSidebar()">☰</button>
  <div class="brand">
    <img src="<?= safe($logoPath) ?>" alt="Logo">
    <span>CSR Dashboard — <?= safe($csr_fullname) ?></span>
  </div>
  <div class="header-spacer"><!-- right spacer keeps brand centered --></div>
</header>

<!-- TOP TABS -->
<div class="tabs">
  <div id="tab-all"  class="tab active" onclick="switchTab('all')">💬 All Clients</div>
  <div id="tab-mine" class="tab"         onclick="switchTab('mine')">👤 My Clients</div>
  <div id="tab-rem"  class="tab"         onclick="switchTab('rem')">⏰ Reminders</div>
  <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
  <div class="tab" onclick="location.href='update_profile.php'">👤 Edit Profile</div>
</div>

<!-- MAIN -->
<div id="main">
  <!-- LEFT: Clients -->
  <div id="client-col"></div>

  <!-- RIGHT: Chat (Messenger-like) -->
  <div id="chat-col">
    <button id="collapseBtn" title="Hide chat" onclick="toggleRight()">…</button>

    <div id="chat-head">
      <div class="chat-title">
        <div class="avatar" id="chatAvatar"></div>
        <div id="chat-title">Select a client</div>
      </div>
      <div class="info-dot" title="Conversation info">i</div>
    </div>

    <div id="messages"></div>

    <div id="input" style="display:none;">
      <input id="msg" placeholder="Type a reply…">
      <button onclick="sendMsg()">Send</button>
    </div>

    <!-- Reminders panel -->
    <div id="reminders" style="display:none;">
      <div id="rem-filter">
        <input id="rem-q" placeholder="Search name/email…" onkeyup="loadReminders()">
      </div>
      <div id="rem-list"></div>
    </div>
  </div>
</div>

<script>
let currentTab = 'all';
let currentClient = 0;
let currentAssignee = '';
const me = <?= json_encode($csr_user) ?>;

/* Sidebar */
function toggleSidebar(force){
  const s = document.getElementById('sidebar');
  const o = document.getElementById('sidebar-overlay');
  const open = s.classList.contains('active');
  const willOpen = (force === true) || (!open && force !== false);
  if (willOpen){ s.classList.add('active'); o.style.display='block'; }
  else { s.classList.remove('active'); o.style.display='none'; }
}

/* Collapse chat column */
function toggleRight(){
  const col = document.getElementById('chat-col');
  const btn = document.getElementById('collapseBtn');
  if (col.classList.contains('collapsed')){
    col.classList.remove('collapsed');
    btn.textContent = '…';
  } else {
    col.classList.add('collapsed');
    btn.textContent = 'i';
  }
}

/* Tabs */
function setActiveTab(id){
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
}
function switchTab(tab){
  currentTab = (tab === 'rem') ? 'all' : tab; // reminders uses all
  setActiveTab(tab);
  if (tab === 'rem'){
    document.getElementById('chat-head').style.display = 'none';
    document.getElementById('messages').style.display = 'none';
    document.getElementById('input').style.display = 'none';
    document.getElementById('reminders').style.display = 'flex';
    loadReminders();
  } else {
    document.getElementById('chat-head').style.display = 'flex';
    document.getElementById('messages').style.display = 'block';
    document.getElementById('reminders').style.display = 'none';
    loadClients();
  }
}

/* Load clients */
function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
    .then(r => r.text())
    .then(html => {
      const col = document.getElementById('client-col');
      col.innerHTML = html;
      col.querySelectorAll('.client-item').forEach(el => {
        el.addEventListener('click', ()=>selectClient(el));
      });
    });
}

/* Avatar setter */
function setAvatar(name, gender, avatarPath){
  const slot = document.getElementById('chatAvatar');
  slot.innerHTML = '';
  if (avatarPath){
    const img = document.createElement('img');
    img.src = avatarPath;
    slot.appendChild(img);
    return;
  }
  if (gender === 'female' || gender === 'male'){
    const img = document.createElement('img');
    img.src = (gender === 'female') ? '../penguin.png' : '../lion.png';
    slot.appendChild(img);
    return;
  }
  // fallback: initials
  const initials = (name || '?')
    .split(/\s+/).slice(0,2).map(w => w[0]?.toUpperCase() || '').join('');
  slot.textContent = initials || '?';
}

/* Select a client */
function selectClient(el){
  currentClient = parseInt(el.dataset.id, 10);
  currentAssignee = el.dataset.csr || 'Unassigned';
  const name = el.dataset.name || 'Client';
  document.getElementById('chat-title').textContent = name;
  document.getElementById('input').style.display = 'flex';

  // Fetch & ensure profile (persists gender/avatar if missing)
  fetch('csr_dashboard.php?ajax=client_profile&name='+encodeURIComponent(name))
    .then(r => r.json())
    .then(p => {
      setAvatar(name, (p.gender||'').toLowerCase(), p.avatar || '');
    });

  loadChat();
}

/* Load conversation */
function loadChat(){
  if (!currentClient) return;
  fetch('csr_dashboard.php?ajax=load_chat&client_id='+currentClient)
    .then(r => r.json())
    .then(rows => {
      const box = document.getElementById('messages');
      box.innerHTML = '';
      rows.forEach(m => {
        const who = (m.sender_type === 'csr') ? (m.csr_fullname || 'CSR') : (m.client_name || 'Client');
        box.insertAdjacentHTML('beforeend', `
          <div class="msg ${m.sender_type}">
            <div class="bubble"><strong>${who}:</strong> ${m.message}</div>
            <div class="meta">${new Date(m.time).toLocaleString()}</div>
          </div>
        `);
      });
      box.scrollTop = box.scrollHeight;
    });
}

/* Send a message */
function sendMsg(){
  if (!currentClient) return;
  if (currentAssignee !== 'Unassigned' && currentAssignee !== me){
    alert('This client is assigned to another CSR.');
    return;
  }
  const input = document.getElementById('msg');
  const text = input.value.trim();
  if (!text) return;

  fetch('../SKYTRUFIBER/save_chat.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      sender_type: 'csr',
      message: text,
      client_id: String(currentClient)
    }).toString()
  }).then(() => {
    input.value = '';
    loadChat();
  });
}

/* Assign / Unassign */
function assignClient(id){
  fetch('csr_dashboard.php?ajax=assign', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'client_id='+encodeURIComponent(id)
  }).then(r=>r.text()).then(t=>{
    if (t === 'taken') alert('Already assigned to another CSR.');
    loadClients();
  });
}
function unassignClient(id){
  if (!confirm('Unassign this client?')) return;
  fetch('csr_dashboard.php?ajax=unassign', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'client_id='+encodeURIComponent(id)
  }).then(()=>loadClients());
}

/* Reminders */
function loadReminders(){
  const q = document.getElementById('rem-q').value.trim();
  fetch('csr_dashboard.php?ajax=reminders&q='+encodeURIComponent(q))
    .then(r => r.json())
    .then(list => {
      const box = document.getElementById('rem-list');
      box.innerHTML = '';
      if (!list.length){
        box.innerHTML = '<div class="card">No upcoming reminders found.</div>';
        return;
      }
      list.forEach(r => {
        let badges = '';
        (r.banners||[]).forEach(b=>{
          const cls = (b.status === 'sent') ? 'sent' : (b.status === 'due' ? 'due' : 'upcoming');
          const txt = (b.type === '1_WEEK' ? '1 week' : '3 days') + ' — ' +
                      (b.status === 'sent' ? 'Sent' : (b.status === 'due' ? 'Due Today' : 'Upcoming')) +
                      ' ('+b.date+')';
          badges += `<span class="badge ${cls}">${txt}</span>`;
        });
        box.insertAdjacentHTML('beforeend', `
          <div class="card">
            <div><strong>${r.name}</strong> &lt;${r.email}&gt;</div>
            <div>Cycle due: <b>${r.due}</b></div>
            <div style="margin-top:6px">${badges}</div>
          </div>
        `);
      });
    });
}

/* Init + live refresh */
switchTab('all');
setInterval(()=>{
  if (document.getElementById('reminders').style.display !== 'none') loadReminders();
  if (document.getElementById('messages').style.display !== 'none' && currentClient) loadChat();
}, 5000);
</script>

</body>
</html>
