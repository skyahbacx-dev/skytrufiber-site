// ========================================
// SkyTruFiber CSR Chat System
// chat.js — Combined Media + Text, Inline Preview, Carousel
// ========================================

let currentClientID = null;
let messageInterval = null;
let clientRefreshInterval = null;
let selectedFiles = [];
let lastMessageID = 0;

$(document).ready(function () {

    loadClients();
    clientRefreshInterval = setInterval(loadClients, 4000);

    // SEARCH CLIENT
    $("#client-search").on("keyup", function () {
        const q = $(this).val().toLowerCase();
        $("#client-list .client-item").each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    // SEND BUTTON ACTION
    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // OPEN FILE PICKER
    $("#upload-btn").click(() => $("#chat-upload-media").click());

    // SELECT FILES
    $("#chat-upload-media").change(function () {
        if (!currentClientID) return;
        selectedFiles = Array.from(this.files);
        if (selectedFiles.length) previewMultiple(selectedFiles);
    });

    // SELECT CLIENT
    $(document).on("click", ".client-item", function () {
        currentClientID = $(this).data("id");
        $("#chat-client-name").text($(this).data("name"));
        $("#chat-messages").html("");
        lastMessageID = 0;

        loadClientInfo(currentClientID);
        loadMessages(true);

        if (messageInterval) clearInterval(messageInterval);
        messageInterval = setInterval(() => {
            if (!$("#preview-inline").is(":visible")) loadMessages(false);
        }, 1500);
    });

    // LIGHTBOX
    $(document).on("click", ".media-thumb", function () {
        $("#lightbox-image").attr("src", $(this).attr("src"));
        $("#lightbox-overlay").fadeIn(200);
    });

    $("#lightbox-close, #lightbox-overlay").click(() =>
        $("#lightbox-overlay").fadeOut(200)
    );

    // REMOVE SINGLE FILE
    $(document).on("click", ".preview-remove", function () {
        const index = $(this).data("index");
        selectedFiles.splice(index, 1);
        previewMultiple(selectedFiles);

        if (selectedFiles.length === 0) $("#preview-inline").slideUp(200);
    });

});


// ========================================
// LOAD CLIENT LIST
function loadClients() {
    $.post("../chat/load_clients.php", function (html) {
        $("#client-list").html(html);
    });
}


// ========================================
// LOAD CLIENT INFO
function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, function (html) {
        $("#client-info-content").html(html);
    });
}


// ========================================
// LOAD NEW MESSAGES WITHOUT DUPLICATION
function loadMessages(scrollBottom = false) {
    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {

        const $incoming = $(html);
        const newLastID = parseInt($incoming.last().attr("data-msg-id"));

        if (newLastID > lastMessageID) {
            lastMessageID = newLastID;
            $("#chat-messages").append($incoming);

            if (scrollBottom) {
                const box = $("#chat-messages");
                box.scrollTop(box[0].scrollHeight);
            }
        }
    });
}


// ========================================
// SEND TEXT OR MEDIA (combined)
function sendMessage() {
    const msg = $("#chat-input").val().trim();

    // If media selected, send both
    if (selectedFiles.length > 0) {
        uploadMedia(selectedFiles, msg);
        return;
    }

    // If no media, send text only
    if (!msg || !currentClientID) return;

    $.post("../chat/send_message.php", {
        client_id: currentClientID,
        message: msg,
        sender_type: "csr"
    }, function (res) {
        if (res.status === "ok") {
            $("#chat-input").val("");
            loadMessages(true);
        }
    }, "json");
}


// ========================================
// PREVIEW MULTIPLE FILES INLINE
function previewMultiple(files) {
    $("#preview-files").html("");

    files.forEach((file, index) => {
        const removeBtn = `<button class="preview-remove" data-index="${index}">&times;</button>`;

        if (file.type.startsWith("image")) {
            const reader = new FileReader();
            reader.onload = e => {
                $("#preview-files").append(`
                    <div class="preview-item">
                        <img src="${e.target.result}" class="preview-thumb">
                        ${removeBtn}
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        } else {
            $("#preview-files").append(`
                <div class="preview-item file-box">${file.name}
                    ${removeBtn}
                </div>
            `);
        }
    });

    $("#preview-inline").slideDown(200);
}


// ========================================
// UPLOAD MEDIA + OPTIONAL TEXT
function uploadMedia(files, msg = "") {

    const fd = new FormData();
    files.forEach(file => fd.append("media[]", file));

    fd.append("client_id", currentClientID);
    fd.append("sender_type", "csr");
    fd.append("message", msg);

    $("#preview-inline").slideUp(200);
    selectedFiles = [];
    $("#chat-upload-media").val("");
    $("#chat-input").val("");

    $.ajax({
        url: "../chat/media_upload.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        dataType: "json",

        success: res => loadMessages(true),
        error: err => console.error("Upload Error:", err.responseText)
    });
}


// ========================================
// ASSIGN / UNASSIGN CLIENT
function assignClient(id) {
    $.post("../chat/assign_client.php", { client_id: id }, () => {
        loadClients();
        if (id == currentClientID) loadClientInfo(id);
    });
}

function unassignClient(id) {
    $.post("../chat/unassign_client.php", { client_id: id }, () => {
        loadClients();
        if (id == currentClientID) $("#client-info-content").html("<p>Select a client.</p>");
    });
}
