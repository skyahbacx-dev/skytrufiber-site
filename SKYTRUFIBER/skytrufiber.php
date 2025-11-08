<?php
include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SkyTruFiber - Technician Survey</title>
  <style>
    body {
      font-family:"Segoe UI", Arial, sans-serif;
      margin:0;
      background:linear-gradient(to bottom right,#cceeff,#e6f7ff);
      min-height:100vh;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      overflow-x:hidden;
    }
    header { width:100%; text-align:center; padding:20px; }
    header img { width:200px; transition:transform .3s ease; }
    header img:hover { transform:scale(1.05); }
    h2 { color:#004466; margin-bottom:10px; }
    form {
      background:#fff; padding:25px; border-radius:15px;
      box-shadow:0 4px 12px rgba(0,0,0,.15); width:400px; text-align:left;
    }
    label { display:block; margin-bottom:6px; font-weight:600; color:#004466; }
    input, select, textarea {
      width:100%; padding:10px; margin-bottom:15px;
      border:1px solid #ccc; border-radius:8px; font-size:14px;
    }
    button {
      background:#0099cc; color:#fff; border:none;
      padding:10px 18px; border-radius:8px; cursor:pointer; font-size:14px;
    }
    button:hover { background:#007a99; }
    footer { text-align:center; margin-top:20px; color:#004466; font-size:13px; }

    /* Floating chat button */
    #chat-toggle {
      position:fixed; bottom:20px; right:25px;
      background:#009900; color:#fff; width:60px; height:60px;
      border-radius:50%; display:flex; align-items:center; justify-content:center;
      font-size:28px; box-shadow:0 4px 10px rgba(0,0,0,.3);
      cursor:pointer; z-index:2000; transition:transform .3s ease, background .3s ease;
    }
    #chat-toggle:hover { transform:scale(1.1); background:#00cc00; }

    /* Chat box */
    #chatbox {
      position:fixed; bottom:90px; right:25px; width:340px; height:460px;
      background:#fff; border-radius:15px; box-shadow:0 8px 22px rgba(0,0,0,.2);
      display:flex; flex-direction:column; overflow:hidden; transition:all .3s ease; z-index:1500;
    }
    #chatbox.hidden { opacity:0; transform:translateY(20px); pointer-events:none; }
    #chat-header { background:#009900; color:#fff; padding:10px; font-weight:700; text-align:center; position:relative; }
    #chat-options { position:absolute; right:10px; top:5px; cursor:pointer; font-size:20px; }
    #options-menu { display:none; position:absolute; right:10px; top:35px; background:#fff; border:1px solid #ccc; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.2); z-index:100; }
    #options-menu button { display:block; width:100%; border:none; background:none; padding:10px; cursor:pointer; font-size:14px; text-align:left; color:#222; }
    #options-menu button:hover { background:#f0f0f0; }
    #chat-messages { flex:1; padding:10px; overflow-y:auto; font-size:14px; background:#f7fff7; display:flex; flex-direction:column; }
    .message { max-width:80%; margin:5px 0; padding:8px 10px; border-radius:15px; display:inline-block; word-wrap:break-word; }
    .message.user { align-self:flex-end; background:#009900; color:#fff; border-bottom-right-radius:2px; }
    .message.csr { align-self:flex-start; background:#e0e0e0; color:#222; border-bottom-left-radius:2px; }
    #chat-input { display:flex; gap:8px; padding:10px; border-top:1px solid #ddd; }
    #chat-input input { flex:1; padding:8px; border:1px solid #ccc; border-radius:8px; font-size:14px; }
    #chat-input button { background:#009900; color:#fff; border:none; border-radius:8px; padding:8px 12px; cursor:pointer; }
    #chat-input button:hover { background:#007a00; }
  </style>
</head>
<body>

<header>
  <img src="SKYTRUFIBER.png" alt="SkyTruFiber Logo">
</header>

<main>
  <h2>Customer Feedback Form</h2>
  <form action="save_survey.php" method="POST">
    <label for="client_name">Client's Name:</label>
    <input type="text" id="client_name" name="client_name" placeholder="Enter your full name" required>

    <label for="account_name">Account Name:</label>
    <input type="text" id="account_name" name="account_name" placeholder="Enter your account name or ID" required>

   <!-- 🏙️ Barangay Dropdown -->
<label for="location">Location (Barangay, Quezon City):</label>
<select id="location" name="location" required>
  <option value="">Select your barangay</option>
 <?php include 'barangay_list.php'; // optional move of list if too long ?>
  <optgroup label="District 1">
    <option>Alicia (Bago Bantay)</option>
    <option>Bagong Pag-asa (North EDSA / Triangle Park)</option>
    <option>Bahay Toro (Project 8)</option>
    <option>Balingasa (Balintawak / Cloverleaf)</option>
    <option>Bungad (Project 7)</option>
    <option>Damar</option>
    <option>Damayan (San Francisco del Monte / Frisco)</option>
    <option>Del Monte (San Francisco del Monte / Frisco)</option>
    <option>Katipunan (Muñoz)</option>
    <option>Lourdes (Sta. Mesa Heights)</option>
    <option>Maharlika (Sta. Mesa Heights)</option>
    <option>Manresa</option>
    <option>Mariblo (SFDM / Frisco)</option>
    <option>Masambong</option>
    <option>N.S. Amoranto (Gintong Silahis, La Loma)</option>
    <option>Nayong Kanluran</option>
    <option>Paang Bundok (La Loma)</option>
    <option>Pag-ibig sa Nayon (Balintawak)</option>
    <option>Paltok (SFDM / Frisco)</option>
    <option>Paraiso (SFDM / Frisco)</option>
    <option>Phil-Am (West Triangle)</option>
    <option>Project 6 (Diliman / Triangle Park)</option>
    <option>Ramon Magsaysay (Bago Bantay)</option>
    <option>Saint Peter (Sta. Mesa Heights)</option>
    <option>Salvacion (La Loma)</option>
    <option>San Antonio (SFDM / Frisco)</option>
    <option>San Isidro Labrador (La Loma)</option>
    <option>San Jose (La Loma)</option>
    <option>Santa Cruz (Pantranco / Heroes Hill)</option>
    <option>Santa Teresita (Sta. Mesa Heights)</option>
    <option>Santo Domingo (Matalahib)</option>
    <option>Siena</option>
    <option>Sto. Cristo (Bago Bantay)</option>
    <option>Talayan</option>
    <option>Vasra (Diliman)</option>
    <option>Veterans Village (Project 7 / Muñoz)</option>
    <option>West Triangle</option>
  </optgroup>

  <optgroup label="District 2">
    <option>Bagong Silangan</option>
    <option>Batasan Hills (Constitution Hills)</option>
    <option>Commonwealth (Manggahan)</option>
    <option>Holy Spirit (Don Antonio)</option>
    <option>Payatas</option>
  </optgroup>

  <optgroup label="District 3">
    <option>Camp Aguinaldo</option>
    <option>Pansol (Balara)</option>
    <option>Mangga (Cubao)</option>
    <option>San Roque (Cubao)</option>
    <option>Silangan (Cubao)</option>
    <option>Socorro (Araneta City)</option>
    <option>Bagumbayan (Eastwood)</option>
    <option>Libis (Eastwood)</option>
    <option>Ugong Norte (Green Meadows / Corinthian / Ortigas)</option>
    <option>Masagana (Jacobo Zobel)</option>
    <option>Loyola Heights (Katipunan)</option>
    <option>Matandang Balara (Old Balara)</option>
    <option>East Kamias (Project 1)</option>
    <option>Quirino 2-A (Project 2 / Anonas)</option>
    <option>Quirino 2-B (Project 2 / Anonas)</option>
    <option>Quirino 2-C (Project 2 / Anonas)</option>
    <option>Amihan (Project 3)</option>
    <option>Claro (Quirino 3-B)</option>
    <option>Duyan-duyan (Project 3)</option>
    <option>Quirino 3-A (Project 3 / Anonas)</option>
    <option>Bagumbuhay (Project 4)</option>
    <option>Bayanihan (Project 4)</option>
    <option>Blue Ridge A (Project 4)</option>
    <option>Blue Ridge B (Project 4)</option>
    <option>Dioquino Zobel (Project 4)</option>
    <option>Escopa I</option>
    <option>Escopa II</option>
    <option>Escopa III</option>
    <option>Escopa IV</option>
    <option>Marilag (Project 4)</option>
    <option>Milagrosa (Project 4)</option>
    <option>Tagumpay (Project 4)</option>
    <option>Villa Maria Clara (Project 4)</option>
    <option>E. Rodriguez (Project 5 / Cubao)</option>
    <option>West Kamias (Project 5 / Kamias)</option>
    <option>St. Ignatius</option>
    <option>White Plains</option>
  </optgroup>

  <optgroup label="District 4">
    <option>Bagong Lipunan ng Crame (Camp Crame)</option>
    <option>Botocan (Diliman)</option>
    <option>Central (Diliman)</option>
    <option>Damayang Lagi (New Manila)</option>
    <option>Don Manuel (Galas)</option>
    <option>Doña Aurora (Galas)</option>
    <option>Doña Imelda (Sta. Mesa / Galas)</option>
    <option>Doña Josefa (Galas)</option>
    <option>Horseshoe</option>
    <option>Immaculate Concepcion (Cubao)</option>
    <option>Kalusugan (St. Luke’s)</option>
    <option>Kamuning</option>
    <option>Kaunlaran (Cubao)</option>
    <option>Kristong Hari</option>
    <option>Krus na Ligas (Diliman)</option>
    <option>Laging Handa (Diliman)</option>
    <option>Malaya (Diliman)</option>
    <option>Mariana (New Manila)</option>
    <option>Obrero (Project 1)</option>
    <option>Old Capitol Site (Diliman)</option>
    <option>Paligsahan (Diliman)</option>
    <option>Pinagkaisahan (Cubao)</option>
    <option>Pinyahan (Triangle Park)</option>
    <option>Roxas (Project 1)</option>
    <option>Sacred Heart (Kamuning)</option>
    <option>San Isidro Galas (Galas)</option>
    <option>San Martin de Porres (Cubao)</option>
    <option>San Vicente (Diliman)</option>
    <option>Santol</option>
    <option>Sikatuna Village (Diliman)</option>
    <option>South Triangle (Diliman)</option>
    <option>Sto. Niño (Galas)</option>
    <option>Tatalon</option>
    <option>Teacher's Village East (Diliman)</option>
    <option>Teacher's Village West (Diliman)</option>
    <option>U.P. Campus (Diliman)</option>
    <option>U.P. Village (Diliman)</option>
    <option>Valencia (Gilmore / N. Domingo)</option>
  </optgroup>

  <optgroup label="District 5">
    <option>Bagbag (Novaliches)</option>
    <option>Capri (Novaliches)</option>
    <option>Fairview (La Mesa / Novaliches)</option>
    <option>Greater Lagro (Lagro / Novaliches)</option>
    <option>Gulod (Novaliches)</option>
    <option>Kaligayahan (Zabarte)</option>
    <option>Nagkaisang Nayon (General Luis)</option>
    <option>North Fairview</option>
    <option>Novaliches Proper (Bayan)</option>
    <option>Pasong Putik Proper</option>
    <option>San Agustin (Susano)</option>
    <option>San Bartolome (Holy Cross)</option>
    <option>Sta. Lucia (San Gabriel)</option>
    <option>Sta. Monica</option>
  </optgroup>

  <optgroup label="District 6">
    <option>Apolonio Samson (Balintawak)</option>
    <option>Baesa (Project 8)</option>
    <option>Balong Bato (Balintawak)</option>
    <option>Culiat (Tandang Sora)</option>
    <option>New Era (Central / INC)</option>
    <option>Pasong Tamo (Tandang Sora)</option>
    <option>Sangandaan (Project 8)</option>
    <option>Sauyo (Novaliches)</option>
    <option>Talipapa (Novaliches)</option>
    <option>Tandang Sora (Banlat)</option>
    <option>Unang Sigaw (Balintawak)</option>
  </optgroup>
  
</select>

    <label for="feedback">Feedback:</label>
    <textarea id="feedback" name="feedback" rows="4" placeholder="Write your comments or suggestions here..." required></textarea>

    <button type="submit">Submit Feedback</button>
  </form>
</main>

<footer>
  <p>SkyTruFiber © 2025 | Customer & Technician Survey Portal</p>
</footer>

<!-- Floating Chat -->
<div id="chat-toggle">💬</div>
<div id="chatbox" class="hidden">
  <div id="chat-header">
    💬 CSR Chat Support
    <div id="chat-options">⋮</div>
    <div id="options-menu">
      <button id="clearChat">🗑️ Clear Chat</button>
      <button id="newChat">✨ Start New Chat</button>
    </div>
  </div>
  <div id="chat-messages">Enter your name to start…</div>
  <div id="chat-input">
    <input type="text" id="username" placeholder="Your name (required)">
    <input type="text" id="message" placeholder="Type a message…">
    <button id="sendBtn">Send</button>
  </div>
</div>

<script>
<!-- ✅ Add Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
  $('#location').select2({
    placeholder: "Search or select your barangay",
    width: '100%'
  });
});
/* --- Chat Script --- */
const chatBox=document.getElementById('chatbox');
const chatToggle=document.getElementById('chat-toggle');
const chatMessages=document.getElementById('chat-messages');
const usernameEl=document.getElementById('username');
const messageEl=document.getElementById('message');
const sendBtn=document.getElementById('sendBtn');
const optionsBtn=document.getElementById('chat-options');
const menu=document.getElementById('options-menu');
setInterval(()=>{ if(!chatBox.classList.contains('hidden')) loadChat(); checkTypingStatus(); },2000);

function checkTypingStatus(){
  const username=usernameEl.value.trim();
  if(!username)return;
  fetch('typing_status.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=check&client_name='+encodeURIComponent(username)})
  .then(r=>r.json()).then(data=>{
    const existing=document.getElementById('typingNotice');
    if(data.typing){
      if(!existing){
        const notice=document.createElement('div');
        notice.id='typingNotice';notice.classList.add('message','system');
        notice.textContent=data.csr+' is typing...';
        chatMessages.appendChild(notice);
        chatMessages.scrollTop=chatMessages.scrollHeight;
      }
    } else if(existing){ existing.remove(); }
  });
}

const storedName=localStorage.getItem('stf_username');
if(storedName) usernameEl.value=storedName;
optionsBtn.addEventListener('click',()=>{menu.style.display=menu.style.display==='block'?'none':'block';});
document.addEventListener('click',e=>{if(!optionsBtn.contains(e.target))menu.style.display='none';});
document.getElementById('clearChat').addEventListener('click',clearLocalChat);
document.getElementById('newChat').addEventListener('click',startNewChat);
chatToggle.addEventListener('click',()=>{chatBox.classList.toggle('hidden');});
sendBtn.addEventListener('click',sendMessage);
messageEl.addEventListener('keydown',e=>{if(e.key==='Enter')sendMessage();});
usernameEl.addEventListener('change',()=>{
  const name=usernameEl.value.trim();
  if(name){localStorage.setItem('stf_username',name);triggerAutoGreeting(name);}
});

function loadChat(){
  const username=usernameEl.value.trim();
  if(!username){chatMessages.innerHTML='<em>Please enter your name to start the chat.</em>';return;}
  fetch('load_chat.php?username='+encodeURIComponent(username))
    .then(res=>res.json()).then(data=>{
      chatMessages.innerHTML='';
      data.forEach(msg=>{
        const div=document.createElement('div');
        div.classList.add('message',msg.sender_type==='csr'?'csr':'user');
        const who=(msg.sender_type==='csr')?(msg.assigned_csr||'CSR'):(msg.client_name||username);
        div.textContent=`${who}: ${msg.message}`;
        chatMessages.appendChild(div);
      });
      chatMessages.scrollTop=chatMessages.scrollHeight;
    });
}

function sendMessage(){
  const username=usernameEl.value.trim();
  const message=messageEl.value.trim();
  if(!username){alert('Please enter your name first.');usernameEl.focus();return;}
  if(!message)return;
  fetch('save_chat.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'username='+encodeURIComponent(username)+'&message='+encodeURIComponent(message)})
  .then(r=>r.json()).then(()=>{messageEl.value='';loadChat();});
}

function triggerAutoGreeting(username){
  const greeted=sessionStorage.getItem('greeted_'+username);
  if(greeted)return;
  fetch('auto_greet.php?username='+encodeURIComponent(username))
    .then(res=>res.json()).then(data=>{
      if(data.status==='success'){
        const msg=document.createElement('div');
        msg.classList.add('message','csr');
        msg.textContent=`CSR ${data.csr}: ${data.message}`;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop=chatMessages.scrollHeight;
      }
      sessionStorage.setItem('greeted_'+username,'true');
    });
}

function clearLocalChat(){ chatMessages.innerHTML='<em>Your chat history was cleared locally. CSR can still see your past messages.</em>'; }
function startNewChat(){
  const username=usernameEl.value.trim();
  if(!username)return alert('Enter your name first.');
  if(!confirm('Start a new chat session?'))return;
  fetch('start_new_chat.php?username='+encodeURIComponent(username))
    .then(()=>{chatMessages.innerHTML='<em>New chat started. Your previous chat was archived.</em>';sessionStorage.clear();});
}
</script>
</body>
</html>
