/* ========================
   GLOBAL VARIABLES
======================== */
let currentClient = 0;
let selectedFiles = [];
let refreshTimer = null;
let csr_user = CSR_USERNAME;
let csr_fullname = CSR_FULLNAME;

/* ========================
   LOAD CLIENT LIST
======================== */
function loadClients(tab="all"){
    fetch("client_list.php?tab="+tab)
    .then(r=>r.text())
    .then(html=>{
        document.getElementById("clientList").innerHTML = html;
    });
}

/* ========================
   OPEN CLIENT CHAT
======================== */
function openClient(id, name){
    currentClient = id;

    document.getElementById("chatName").innerText = name;
    document.getElementById("chatAvatar").src = "CSR/lion.PNG";

    document.getElementById("messageInput").disabled = false;
    document.getElementById("sendBtn").disabled = false;
    document.getElementById("chatMessages").innerHTML = "";

    loadChat();

    if(refreshTimer) clearInterval(refreshTimer);
    refreshTimer = setInterval(loadChat, 1000);
}

/* ========================
   LOAD CHAT MESSAGES
======================== */
function loadChat(){
    if(!currentClient) return;

    fetch("load_chat_csr.php?client_id=" + currentClient)
    .then(r=>r.json())
    .then(list=>{
        const box = document.getElementById("chatMessages");
        box.innerHTML = "";

        if(list.length === 0){
            box.innerHTML = `<p class="placeholder">No messages yet.</p>`;
            return;
        }

        list.forEach(m=>{
            let mediaHtml = "";

            if(m.media_path){
                if(m.media_type === "image"){
                    mediaHtml = `<img src="${m.media_path}" class="file-img">`;
                } else if(m.media_type === "video"){
                    mediaHtml = `<video controls class="file-video">
                                   <source src="${m.media_path}">
                                 </video>`;
                }
            }

            box.insertAdjacentHTML("beforeend",`
                <div class="msg ${m.sender_type}">
                    <div class="bubble">${m.message || ""}${mediaHtml}
                    <div class="meta">${m.created_at}</div></div>
                </div>
            `);
        });

        box.scrollTop = box.scrollHeight;
    });
}

/* ========================
   FILE PREVIEW GRID
======================== */
document.getElementById("fileUpload").addEventListener("change", function(){
    for(let file of this.files){
        selectedFiles.push(file);
    }
    renderPreview();
});

function renderPreview(){
    const preview = document.getElementById("uploadPreview");
    preview.innerHTML = "";
    preview.style.display = "flex";

    selectedFiles.forEach((file,index)=>{
        const url = URL.createObjectURL(file);

        preview.innerHTML += `
            <div class="photo-item">
                <span class="remove-photo" onclick="removePreview(${index})">✖</span>
                <img src="${url}">
            </div>
        `;
    });
}

function removePreview(index){
    selectedFiles.splice(index,1);
    if(selectedFiles.length===0){
        document.getElementById("uploadPreview").style.display="none";
    }
    renderPreview();
}

/* ========================
   SEND MESSAGE + MEDIA
======================== */
document.getElementById("sendBtn").addEventListener("click", sendMessage);
document.getElementById("messageInput").addEventListener("keyup", e=>{if(e.key==="Enter") sendMessage();});

function sendMessage(){
    if(!currentClient) return;

    const text = document.getElementById("messageInput").value.trim();
    if(text==="" && selectedFiles.length===0) return;

    const fd = new FormData();
    fd.append("sender_type","csr");
    fd.append("message",text);
    fd.append("client_id",currentClient);
    fd.append("csr_user",csr_user);
    fd.append("csr_fullname",csr_fullname);

    selectedFiles.forEach(file=>{
        fd.append("files[]",file);
    });

    fetch("save_chat_csr.php",{method:"POST",body:fd})
    .then(r=>r.json())
    .then(res=>{
        if(res.status==="ok"){
            document.getElementById("messageInput").value = "";
            selectedFiles = [];
            document.getElementById("uploadPreview").style.display="none";
            loadChat();
        }
    });
}
