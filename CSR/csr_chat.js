/**************** GLOBALS ****************/
let selectedClient = 0;
let assignedTo = "";
let filesToSend = [];
let galleryItems = [];   // store media paths for swipe gallery
let galleryIndex = 0;    // current opened index

/**************** SIDEBAR ****************/
function toggleSidebar(){
    document.querySelector(".sidebar").classList.toggle("open");
    document.querySelector(".sidebar-overlay").classList.toggle("show");
}

/**************** INFO PANEL ****************/
function toggleClientInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("show");
}

/**************** LOAD CLIENT LIST ****************/
function loadClients() {
    $.get("client_list.php", data => {
        $("#clientList").html(data);
    });

    $(".search").on("keyup", function(){
        let txt = $(this).val();
        $.get("client_list.php?search=" + txt, data => {
            $("#clientList").html(data);
        });
    });
}

/**************** SELECT CLIENT ****************/
function selectClient(id, name, assigned) {
    selectedClient = id;
    assignedTo = assigned;

    $(".client-item").removeClass("active-client");
    $("#client-" + id).addClass("active-client");

    $("#chatName").text(name);

    let locked = assigned && assigned !== csrFullname;
    $("#messageInput").prop("disabled", locked);
    $("#sendBtn").prop("disabled", locked);
    if (locked) {
        $(".upload-icon").hide();
        $("#fileInput").prop("disabled", true);
    } else {
        $(".upload-icon").show();
        $("#fileInput").prop("disabled", false);
    }

    loadClientInfo();
    loadMessages();
}

/**************** ASSIGN & UNASSIGN ****************/
function assignClient(id){
    $.post("assign_client.php", { client_id:id }, function(){
        loadClients();
    });
}

function unassignClient(id){
    if(!confirm("Remove this client from your list?")) return;
    $.post("unassign_client.php", { client_id:id }, function(){
        loadClients();
    });
}

/**************** LOAD CLIENT INFO PANEL ****************/
function loadClientInfo(){
    $.getJSON("client_info.php?id=" + selectedClient, info => {
        $("#infoName").text(info.name);
        $("#infoEmail").text(info.email);
        $("#infoDistrict").text(info.district);
        $("#infoBrgy").text(info.barangay);
    });
}

/**************** LOAD MESSAGES (CHAT) ****************/
function loadMessages(){
    if (!selectedClient) return;

    $.getJSON("load_chat_csr.php?client_id=" + selectedClient, messages => {
        let html = "";
        galleryItems = [];  // reset gallery

        messages.forEach((m, index) => {
            let side = (m.sender_type === "csr") ? "csr" : "client";

            html += `<div class="msg ${side}">
                        <div class="bubble">${m.message || ""}`;

            if (m.media_path) {
                galleryItems.push(m.media_path);   // store for viewer

                if (m.media_type === "image") {
                    html += `
                        <img src="${m.media_path}" 
                             class="file-img" 
                             onclick="openMedia(${galleryItems.length - 1})">
                    `;
                } else {
                    html += `
                        <video class="file-img" controls onclick="openMedia(${galleryItems.length - 1})">
                            <source src="${m.media_path}">
                        </video>
                    `;
                }
            }

            html += `<div class="meta">${m.created_at}</div>
                     </div></div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/**************** FILE PREVIEW ****************/
$("#fileInput").on("change", function(e){
    filesToSend = [...e.target.files];
    $("#previewArea").html("");

    filesToSend.forEach(file => {
        let reader = new FileReader();
        reader.onload = ev => {
            $("#previewArea").append(`
                <div class="preview-thumb">
                    ${file.type.includes("video")
                        ? `<video src="${ev.target.result}" muted></video>`
                        : `<img src="${ev.target.result}">`
                    }
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
});

/**************** SEND MESSAGE ****************/
$("#sendBtn").click(function(){
    let msg = $("#messageInput").val();
    if (!msg && filesToSend.length === 0) return;

    let fd = new FormData();
    fd.append("message", msg);
    fd.append("client_id", selectedClient);
    fd.append("csr_fullname", csrFullname);

    filesToSend.forEach(f => fd.append("files[]", f));

    $.ajax({
        url:"save_chat_csr.php",
        method:"POST",
        data:fd,
        processData:false,
        contentType:false,
        success:function(){
            $("#messageInput").val("");
            $("#previewArea").html("");
            $("#fileInput").val("");
            filesToSend = [];
            loadMessages();
        }
    });
});

/**************** FULLSCREEN MEDIA VIEWER + SWIPE ****************/
function openMedia(i){
    galleryIndex = i;
    showMedia();
    $("#mediaModal").addClass("show");
}

function showMedia(){
    $("#mediaModalContent").attr("src", galleryItems[galleryIndex]);
}

$("#closeMediaModal").click(() => $("#mediaModal").removeClass("show"));

$(".swipe-left").click(() => {
    galleryIndex = (galleryIndex - 1 + galleryItems.length) % galleryItems.length;
    showMedia();
});

$(".swipe-right").click(() => {
    galleryIndex = (galleryIndex + 1) % galleryItems.length;
    showMedia();
});

/* Swipe gesture support */
let startX = null;
$("#mediaModal").on("touchstart", e => startX = e.touches[0].clientX);
$("#mediaModal").on("touchend", e => {
    let endX = e.changedTouches[0].clientX;
    if (startX - endX > 80) $(".swipe-right").click(); // slide left
    if (endX - startX > 80) $(".swipe-left").click();  // slide right
});

/* Keyboard navigation */
$(document).on("keydown", e => {
    if (!$("#mediaModal").hasClass("show")) return;

    if (e.key === "ArrowRight") $(".swipe-right").click();
    if (e.key === "ArrowLeft")  $(".swipe-left").click();
    if (e.key === "Escape")     $("#mediaModal").removeClass("show");
});

/**************** AUTO REFRESH ****************/
setInterval(loadClients, 4000);
setInterval(loadMessages, 1500);

loadClients();
