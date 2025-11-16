let selectedClient = 0;
let previewFiles = [];

function toggleSidebar(){
  document.getElementById("sidebar").classList.toggle("active");
  document.getElementById("overlay").classList.toggle("show");
}

function openInfo(){ document.getElementById("clientInfoPanel").classList.add("active"); }
function closeInfo(){ document.getElementById("clientInfoPanel").classList.remove("active"); }

/* LOAD CLIENT LIST */
function loadClients(){
  $.get("client_list.php", res => $("#clientList").html(res));
}

/* LOAD CHAT MESSAGES */
function loadMessages(){
  if(!selectedClient) return;
  $.get("load_chat_csr.php?client_id="+selectedClient, res => {
    let html = "";
    res.forEach(m => {
      let side = (m.sender_type==="csr")?"csr":"client";
      html += `
      <div class='msg ${side}'>
        <div class='bubble'>${m.message || ""}</div>
        ${m.media_path ? mediaHTML(m) : ""}
        <div class='meta'>${m.created_at}</div>
      </div>`;
    });
    $("#chatMessages").html(html);
    $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
  });
}

function mediaHTML(m){
  if(m.media_type==="image") return `<img src="CSR/upload/chat/${m.media_path}" class="file-img">`;
  if(m.media_type==="video") return `<video controls class="file-video"><source src="CSR/upload/chat/${m.media_path}"></video>`;
}

/* SELECT CLIENT */
function selectClient(id,name){
  selectedClient=id;
  $("#chatName").text(name);
  $("#messageInput").prop("disabled",false);
  loadMessages();
}

/* PREVIEW IMAGES */
$("#attachBtn").click(()=>$("#fileInput").click());
$("#fileInput").change(function(){
  previewFiles = Array.from(this.files);
  $("#previewFiles").html("");
  previewFiles.forEach((file,i) => {
    let url = URL.createObjectURL(file);
    $("#previewFiles").append(`
      <div class="preview-item">
        <img src="${url}">
        <span class="remove-photo" onclick="removePreview(${i})">×</span>
      </div>`);
  });
});
function removePreview(i){
  previewFiles.splice(i,1);
  $("#previewFiles").children().eq(i).remove();
}

/* SEND MESSAGE */
$("#sendBtn").click(()=>{
  let message=$("#messageInput").val();
  let form = new FormData();
  form.append("message",message);
  form.append("client_id",selectedClient);
  form.append("csr_fullname","<?php echo $csrFullName; ?>");

  previewFiles.forEach(f=>form.append("files[]",f));
  $.ajax({
    url:"save_chat_csr.php",
    type:"POST",
    data:form,
    processData:false,
    contentType:false,
    success:()=>{
      $("#messageInput").val("");
      previewFiles=[]; $("#previewFiles").html("");
      loadMessages();
    }
  });
});

setInterval(loadMessages,2000);
loadClients();
