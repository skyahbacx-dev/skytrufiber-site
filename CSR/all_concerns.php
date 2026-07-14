<?php
/* ============================================================
   ALL CONCERNS — Supervisor console
   Read-only view of every subscriber's concerns:
   tickets + chats + survey feedback, incl. previous & test data.
============================================================ */

ini_set("session.name", "CSRSESSID");
session_start();

/* Must be logged in as a CSR */
if (empty($_SESSION['csr_user'])) {
    header("Location: /csr");
    exit;
}

require __DIR__ . "/../../db_connect.php";
require __DIR__ . "/admin_guard.php";

$csrUser = $_SESSION['csr_user'];

/* ------------------------------------------------------------
   Helper: format a timestamp safely
------------------------------------------------------------ */
function ac_fmt($ts) {
    if (!$ts) return "";
    $t = strtotime((string)$ts);
    return $t ? date("M j, Y g:i A", $t) : (string)$ts;
}

/* Helper: run a query, never throw */
function ac_all($conn, $sql) {
    try {
        $st = $conn->query($sql);
        return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {
        return [];
    }
}

$concerns = [];
$loadError = "";

if (!empty($GLOBALS['CSR_IS_ADMIN'])) {

    $users   = ac_all($conn, "SELECT * FROM users");
    $tickets = ac_all($conn, "SELECT * FROM tickets");
    $chats   = ac_all($conn, "SELECT * FROM chat ORDER BY created_at ASC, id ASC");
    $surveys = ac_all($conn, "SELECT * FROM survey_responses");

    /* Index tickets / chats / surveys by client */
    $ticketsByClient = [];
    foreach ($tickets as $t) {
        $cid = $t['client_id'] ?? null;
        if ($cid === null) continue;
        $ticketsByClient[$cid][] = $t;
    }

    $chatsByClient = [];
    foreach ($chats as $m) {
        if (!empty($m['deleted'])) continue;
        $cid = $m['client_id'] ?? null;
        if ($cid === null) continue;
        $chatsByClient[$cid][] = $m;
    }

    $surveysByUser = [];
    $surveysByAcct = [];
    foreach ($surveys as $s) {
        if (isset($s['user_id']) && $s['user_id'] !== null && $s['user_id'] !== '') {
            $surveysByUser[$s['user_id']][] = $s;
        }
        if (!empty($s['account_number'])) {
            $surveysByAcct[$s['account_number']][] = $s;
        }
    }

    foreach ($users as $u) {
        $id = $u['id'] ?? null;
        if ($id === null) continue;

        $uTickets = $ticketsByClient[$id] ?? [];
        $uChats   = $chatsByClient[$id] ?? [];
        $acct     = (string)($u['account_number'] ?? '');
        $uSurveys = $surveysByUser[$id] ?? ($surveysByAcct[$acct] ?? []);

        /* Only show subscribers who actually raised a concern */
        if (!$uTickets && !$uChats && !$uSurveys) continue;

        /* Status */
        $latestStatus = "";
        if ($uTickets) {
            $lastT = end($uTickets);
            $latestStatus = strtolower((string)($lastT['status'] ?? ''));
        }
        if ($latestStatus === "") {
            $latestStatus = strtolower((string)($u['ticket_status'] ?? 'unresolved'));
        }
        $status = ($latestStatus === "resolved") ? "resolved" : "pending";

        /* Source + test flag */
        $rawSource = strtolower((string)($u['source'] ?? ''));
        $isTest = (strpos($rawSource, 'test') !== false) || (strncmp($acct, '999', 3) === 0);
        if ($isTest) {
            $sourceLabel = "TEST";
        } elseif (strpos($rawSource, 'field') !== false) {
            $sourceLabel = "FieldOps";
        } elseif ($rawSource === "") {
            $sourceLabel = "Portal";
        } else {
            $sourceLabel = ucfirst($rawSource);
        }

        /* Thread */
        $thread = [];
        foreach ($uChats as $m) {
            $who = (strtolower((string)($m['sender_type'] ?? '')) === 'csr') ? 'csr' : 'client';
            $thread[] = [
                'who'  => $who,
                'text' => (string)($m['message'] ?? ''),
                'at'   => ac_fmt($m['created_at'] ?? '')
            ];
        }
        if (!$thread) {
            $thread[] = ['who' => 'client', 'text' => '(no chat messages yet)', 'at' => ''];
        }

        /* Survey feedback (first non-empty) */
        $surveyText = "";
        foreach ($uSurveys as $s) {
            if (!empty($s['feedback'])) { $surveyText = (string)$s['feedback']; break; }
        }

        $concerns[] = [
            'id'          => $id,
            'name'        => (string)($u['full_name'] ?? '(no name)'),
            'acct'        => $acct,
            'email'       => (string)($u['email'] ?? ''),
            'district'    => (string)($u['district'] ?? ''),
            'brgy'        => (string)($u['barangay'] ?? ''),
            'installed'   => (string)($u['date_installed'] ?? ''),
            'source'      => $sourceLabel,
            'status'      => $status,
            'csr'         => ((string)($u['assigned_csr'] ?? '')) ?: 'unassigned',
            'ticketCount' => count($uTickets),
            'isTest'      => $isTest ? 1 : 0,
            'thread'      => $thread,
            'survey'      => $surveyText,
            'when'        => ac_fmt($u['created_at'] ?? '')
        ];
    }

    /* Pending first, then most recent */
    usort($concerns, function ($a, $b) {
        if ($a['status'] !== $b['status']) return $a['status'] === 'pending' ? -1 : 1;
        return strcmp($b['when'], $a['when']);
    });
}

$CONCERNS_JSON = json_encode(
    $concerns,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
);
if ($CONCERNS_JSON === false) $CONCERNS_JSON = "[]";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Concerns — CSR Console</title>
<style>
:root{
  --green:#007c3c; --green-dark:#015f2e; --green-soft:#e8f7ee;
  --ink:#1f2b25; --muted:#6b7d73; --line:#e2ece6; --bg:#f4f8f5;
  --amber-bg:#fdf1d8; --amber-tx:#8a5b06;
  --blue-bg:#e6f1fb; --blue-tx:#0c447c;
  --grey-bg:#eef2f0; --grey-tx:#5f6f66;
  --green-bg:#e6f6ec; --green-tx:#1c6b3a;
}
*{box-sizing:border-box;}
body{margin:0;font-family:"Segoe UI",system-ui,Arial,sans-serif;color:var(--ink);background:var(--bg);}
.ac-top{display:flex;align-items:center;gap:14px;background:var(--green);color:#fff;padding:10px 18px;flex-wrap:wrap;}
.ac-top img{height:34px;width:34px;border-radius:8px;background:#fff;padding:3px;object-fit:contain;}
.ac-top h1{font-size:16px;margin:0;font-weight:700;letter-spacing:.3px;}
.ac-top .ac-nav{margin-left:auto;display:flex;gap:8px;align-items:center;}
.ac-top a{color:#eafff3;font-size:13px;text-decoration:none;padding:7px 12px;border-radius:8px;background:rgba(255,255,255,.14);}
.ac-top a:hover{background:rgba(255,255,255,.26);}
.ac-wrap{max-width:1280px;margin:0 auto;padding:18px;}
.ac-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;}
.ac-metric{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 16px;}
.ac-metric .l{font-size:12px;color:var(--muted);}
.ac-metric .v{font-size:26px;font-weight:800;margin-top:2px;}
.ac-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:14px;}
.ac-toolbar input[type=text],.ac-toolbar select{padding:9px 12px;border:1px solid var(--line);border-radius:10px;font-size:13px;background:#fff;color:var(--ink);}
.ac-toolbar input[type=text]{flex:1;min-width:200px;}
.ac-seg{display:flex;border:1px solid var(--line);border-radius:10px;overflow:hidden;}
.ac-seg button{border:none;background:#fff;padding:8px 12px;font-size:12px;cursor:pointer;color:var(--muted);}
.ac-seg button.on{background:var(--green);color:#fff;font-weight:700;}
.ac-chk{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--muted);cursor:pointer;}
.ac-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1.4fr);gap:14px;}
.ac-panel{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;}
.ac-panel .ac-phead{padding:11px 14px;border-bottom:1px solid var(--line);font-size:13px;font-weight:700;color:var(--green-dark);display:flex;align-items:center;gap:8px;}
.ac-list{max-height:640px;overflow-y:auto;}
.ac-row{padding:12px 14px;border-bottom:1px solid var(--line);cursor:pointer;}
.ac-row:hover{background:var(--green-soft);}
.ac-row.sel{background:var(--green-soft);border-left:4px solid var(--green);padding-left:10px;}
.ac-r1{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.ac-nm{font-weight:700;font-size:14px;}
.ac-r2{font-size:12px;color:var(--muted);margin-top:3px;}
.ac-snip{font-size:13px;color:#40514a;margin-top:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ac-tag{font-size:11px;padding:2px 8px;border-radius:20px;font-weight:700;white-space:nowrap;}
.ac-t-field{background:var(--blue-bg);color:var(--blue-tx);}
.ac-t-portal{background:var(--grey-bg);color:var(--grey-tx);}
.ac-t-test{background:var(--amber-bg);color:var(--amber-tx);}
.ac-s-pending{background:var(--amber-bg);color:var(--amber-tx);}
.ac-s-resolved{background:var(--green-bg);color:var(--green-tx);}
.ac-when{margin-left:auto;font-size:11px;color:var(--muted);}
.ac-detail{padding:16px;min-height:420px;}
.ac-dhead{display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;}
.ac-avatar{width:46px;height:46px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;}
.ac-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px 18px;background:var(--bg);border-radius:12px;padding:12px 14px;margin-bottom:14px;}
.ac-meta .k{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;}
.ac-meta .val{font-size:13px;font-weight:600;word-break:break-word;}
.ac-tt{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin:6px 0 10px;}
.ac-msg{max-width:80%;padding:9px 12px;border-radius:12px;font-size:13px;line-height:1.45;margin-bottom:8px;}
.ac-msg.client{background:var(--bg);border:1px solid var(--line);}
.ac-msg.csr{background:var(--green);color:#fff;margin-left:auto;}
.ac-msg .mt{font-size:10px;opacity:.7;margin-top:4px;}
.ac-survey{margin-top:14px;background:#fffdf3;border:1px solid #f3e6bf;border-radius:12px;padding:11px 13px;font-size:13px;color:#5f5220;}
.ac-empty{padding:60px 20px;text-align:center;color:var(--muted);font-size:14px;}
.ac-restrict{max-width:520px;margin:80px auto;background:#fff;border:1px solid var(--line);border-radius:16px;padding:34px;text-align:center;}
.ac-restrict h2{color:var(--green-dark);margin:0 0 8px;}
@media(max-width:880px){.ac-grid{grid-template-columns:1fr;}.ac-metrics{grid-template-columns:repeat(2,1fr);}.ac-detail{min-height:0;}}
</style>
</head>
<body>

<div class="ac-top">
  <img src="/AHBALOGO.png" alt="AHBA" onerror="this.style.display='none'">
  <h1>ALL CONCERNS — SUPERVISOR CONSOLE</h1>
  <div class="ac-nav">
    <a href="/csr/dashboard">← Dashboard</a>
    <a href="/csr/logout">Logout</a>
  </div>
</div>

<?php if (empty($GLOBALS['CSR_IS_ADMIN'])): ?>

  <div class="ac-restrict">
    <h2>Access restricted</h2>
    <p style="color:var(--muted);">This console is available to supervisor accounts only.
    You are signed in as <strong><?= htmlspecialchars($csrUser) ?></strong>.</p>
    <p style="color:var(--muted);font-size:13px;">To grant access, add this username to the
    list in <code>CSR/concerns/admin_guard.php</code>.</p>
  </div>

<?php else: ?>

<div class="ac-wrap">

  <div class="ac-metrics">
    <div class="ac-metric"><div class="l">Total concerns</div><div class="v" id="acM-total">0</div></div>
    <div class="ac-metric"><div class="l">Pending</div><div class="v" id="acM-pending" style="color:#b9760a">0</div></div>
    <div class="ac-metric"><div class="l">Resolved</div><div class="v" id="acM-resolved" style="color:#1c6b3a">0</div></div>
    <div class="ac-metric"><div class="l">Test entries</div><div class="v" id="acM-test">0</div></div>
  </div>

  <div class="ac-toolbar">
    <input type="text" id="acQ" placeholder="Search name or account number…">
    <div class="ac-seg" id="acStatusSeg">
      <button data-v="all" class="on">All</button>
      <button data-v="pending">Pending</button>
      <button data-v="resolved">Resolved</button>
    </div>
    <select id="acSource">
      <option value="all">All sources</option>
      <option value="FieldOps">FieldOps</option>
      <option value="Portal">Portal</option>
      <option value="TEST">Test</option>
    </select>
    <select id="acDistrict"><option value="all">All districts</option></select>
    <label class="ac-chk"><input type="checkbox" id="acShowTest" checked> Include test data</label>
  </div>

  <div class="ac-grid">
    <div class="ac-panel">
      <div class="ac-phead">🗂 Concerns <span id="acCount" style="color:var(--muted);font-weight:400;margin-left:auto;"></span></div>
      <div class="ac-list" id="acList"></div>
    </div>
    <div class="ac-panel">
      <div class="ac-phead">📋 Details</div>
      <div class="ac-detail" id="acDetail"><div class="ac-empty">Select a concern on the left to see the subscriber, their tickets, the full chat, and any survey feedback.</div></div>
    </div>
  </div>

</div>

<script>
const AC_DATA = <?= $CONCERNS_JSON ?>;
const acF = {status:"all", source:"all", district:"all", q:"", showTest:true};
let acSel = null;

function acInitials(n){return (n||"?").split(" ").map(w=>w[0]).filter(Boolean).slice(0,2).join("").toUpperCase();}
function acSrcTag(s){return s==="FieldOps"?"ac-t-field":s==="TEST"?"ac-t-test":"ac-t-portal";}
function acEsc(s){return (s==null?"":String(s)).replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));}

(function acInitDistricts(){
  const sel=document.getElementById('acDistrict');
  const set=[...new Set(AC_DATA.map(d=>d.district).filter(Boolean))].sort();
  set.forEach(d=>{const o=document.createElement('option');o.value=d;o.textContent=d;sel.appendChild(o);});
})();

function acFiltered(){
  return AC_DATA.filter(d=>{
    if(!acF.showTest && d.isTest) return false;
    if(acF.status!=="all" && d.status!==acF.status) return false;
    if(acF.source!=="all" && d.source!==acF.source) return false;
    if(acF.district!=="all" && d.district!==acF.district) return false;
    if(acF.q){const q=acF.q.toLowerCase(); if(!((d.name||"").toLowerCase().includes(q)||(d.acct||"").includes(q))) return false;}
    return true;
  });
}

function acRenderList(){
  const list=document.getElementById('acList');
  const items=acFiltered();
  list.innerHTML="";
  document.getElementById('acCount').textContent=items.length+" shown";
  if(!items.length){list.innerHTML='<div class="ac-empty">No concerns match these filters.</div>';}
  items.forEach(d=>{
    const last=d.thread[d.thread.length-1]||{text:""};
    const el=document.createElement('div');
    el.className="ac-row"+(acSel===d.id?" sel":"");
    el.innerHTML=
      '<div class="ac-r1"><span class="ac-nm">'+acEsc(d.name)+'</span>'
      +'<span class="ac-tag '+acSrcTag(d.source)+'">'+acEsc(d.source)+'</span>'
      +'<span class="ac-tag ac-s-'+d.status+'">'+d.status+'</span>'
      +'<span class="ac-when">'+acEsc(d.when)+'</span></div>'
      +'<div class="ac-r2">#'+acEsc(d.acct)+' · '+acEsc(d.district)+' · '+acEsc(d.brgy)+' · CSR: '+acEsc(d.csr)+'</div>'
      +'<div class="ac-snip">💬 '+acEsc(last.text)+'</div>';
    el.onclick=()=>{acSel=d.id;acRenderList();acRenderDetail();};
    list.appendChild(el);
  });
  const pool=acF.showTest?AC_DATA:AC_DATA.filter(d=>!d.isTest);
  document.getElementById('acM-total').textContent=pool.length;
  document.getElementById('acM-pending').textContent=pool.filter(d=>d.status==="pending").length;
  document.getElementById('acM-resolved').textContent=pool.filter(d=>d.status==="resolved").length;
  document.getElementById('acM-test').textContent=AC_DATA.filter(d=>d.isTest).length;
}

function acRenderDetail(){
  const d=AC_DATA.find(x=>x.id===acSel);
  const box=document.getElementById('acDetail');
  if(!d){box.innerHTML='<div class="ac-empty">Select a concern on the left.</div>';return;}
  box.innerHTML=
    '<div class="ac-dhead"><div class="ac-avatar">'+acInitials(d.name)+'</div>'
    +'<div><div style="font-weight:800;font-size:16px;">'+acEsc(d.name)+'</div>'
    +'<div style="font-size:12px;color:var(--muted);">Account #'+acEsc(d.acct)+' · '+d.ticketCount+' ticket(s)</div></div>'
    +'<span class="ac-tag ac-s-'+d.status+'" style="margin-left:auto;">'+d.status+'</span></div>'
    +'<div class="ac-meta">'
    +'<div><div class="k">Email</div><div class="val">'+acEsc(d.email)+'</div></div>'
    +'<div><div class="k">Source</div><div class="val">'+acEsc(d.source)+'</div></div>'
    +'<div><div class="k">District</div><div class="val">'+acEsc(d.district)+'</div></div>'
    +'<div><div class="k">Barangay</div><div class="val">'+acEsc(d.brgy)+'</div></div>'
    +'<div><div class="k">Date installed</div><div class="val">'+acEsc(d.installed)+'</div></div>'
    +'<div><div class="k">Assigned CSR</div><div class="val">'+acEsc(d.csr)+'</div></div>'
    +'</div>'
    +'<div class="ac-tt">Conversation</div>'
    +d.thread.map(m=>'<div class="ac-msg '+(m.who==="csr"?"csr":"client")+'">'+acEsc(m.text)+'<div class="mt">'+(m.who==="csr"?"CSR":acEsc(d.name))+(m.at?' · '+acEsc(m.at):'')+'</div></div>').join('')
    +(d.survey?'<div class="ac-survey">⭐ Survey feedback: “'+acEsc(d.survey)+'”</div>':'');
}

document.querySelectorAll('#acStatusSeg button').forEach(b=>b.onclick=()=>{
  document.querySelectorAll('#acStatusSeg button').forEach(x=>x.classList.remove('on'));
  b.classList.add('on');acF.status=b.dataset.v;acRenderList();
});
document.getElementById('acSource').onchange=e=>{acF.source=e.target.value;acRenderList();};
document.getElementById('acDistrict').onchange=e=>{acF.district=e.target.value;acRenderList();};
document.getElementById('acQ').oninput=e=>{acF.q=e.target.value;acRenderList();};
document.getElementById('acShowTest').onchange=e=>{acF.showTest=e.target.checked;acRenderList();};
acRenderList();
</script>

<?php endif; ?>

</body>
</html>
