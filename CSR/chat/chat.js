// ========================================
// SkyTruFiber CSR Chat System
// Enhanced Media Support + Fullscreen + Carousel
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

    // SEND MESSAGE BUTTON
    $("#send-btn").click(sendMessage);
    $("#chat-input").keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // MEDIA UPLOAD BUTTON
    $("#upload-btn").click(() => $("#chat-upload-media").click());

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
        }, 1200);
    });

    // SCROLL INDICATOR BUTTON
    const chatBox = $("#chat-messages");
    const scrollBtn = $("#scroll-bottom-btn");

    chatBox.on("scroll", function () {
        const atBottom =
            chatBox[0].scrollHeight - chatBox.scrollTop() - chatBox.outerHeight() < 50;
        if (atBottom) scrollBtn.removeClass("show");
        else scrollBtn.addClass("show");
    });

    scrollBtn.click(function () {
        scrollToBottomSmooth();
        scrollBtn.removeClass("show");
    });

});

// ========================================
// Smooth Scroll
// ========================================
function scrollToBottomSmooth() {
    const box = $("#chat-messages");
    box.stop().animate({ scrollTop: box[0].scrollHeight }, 300);
}

// ========================================
// Upload Placeholder
// ========================================
function addUploadingPlaceholder() {
    const tempID = "uploading-" + Date.now();

    $("#chat-messages").append(`
        <div class="message sent" id="${tempID}">
            <div class="message-avatar">
                <img src="/upload/default-avatar.png">
            </div>
            <div class="message-content">
                <div class="message-bubble uploading-bubble">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
                <div class="message-time">Uploading...</div>
            </div>
        </div>
    `);

    scrollToBottomSmooth();
    return tempID;
}

// ========================================
// Load Client List / Info / Messages
// ========================================
function loadClients() {
    $.post("../chat/load_clients.php", function (html) {
        $("#client-list").html(html);
    });
}

function loadClientInfo(id) {
    $.post("../chat/load_client_info.php", { client_id: id }, function (html) {
        $("#client-info-content").html(html);
    });
}

function loadMessages(scrollBottom = false) {
    if (!currentClientID) return;

    $.post("../chat/load_messages.php", { client_id: currentClientID }, function (html) {
        const $incoming = $(html);
        const newLastID = parseInt($incoming.last().attr("data-msg-id"));

        if (newLastID > lastMessageID) {
            lastMessageID = newLastID;
            $("#chat-messages").append($incoming);

            if (scrollBottom) scrollToBottomSmooth();
        }
    });
}

// ========================================
// Send Message or Media
// ========================================
function sendMessage() {
    const msg = $("#chat-input").val().trim();

    if (selectedFiles.length > 0) {
        uploadMedia(selectedFiles, msg);
        return;
    }

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
// Preview Multiple Files
// ========================================
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
// Upload Media
// ========================================
function uploadMedia(files, msg = "") {

    const placeholderID = addUploadingPlaceholder();

    const fd = new FormData();
    files.forEach(f => fd.append("media[]", f));
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

        success: res => {
            $("#" + placeholderID).remove();
            loadMessages(true);
        },
        error: err => {
            console.error("Upload Error:", err.responseText);
            $("#" + placeholderID).remove();
        }
    });
}

// ========================================
// FULLSCREEN MEDIA VIEW
// ========================================
$(document).on("click", ".fullview-item", function () {
    const src = $(this).attr("src");
    const isVid = $(this).is("video, video *");

    if (!$("#fullview-modal").length) {
        $("body").append(`
            <div id="fullview-modal">
                <span id="fullview-close">&times;</span>
                <div id="fullview-content"></div>
            </div>
        `);
    }

    if (isVid) {
        $("#fullview-content").html(`
            <video src="${src}" controls autoplay style="max-width:92%;max-height:92%;border-radius:12px;"></video>
        `);
    } else {
        $("#fullview-content").html(`<img src="${src}" style="max-width:92%;max-height:92%;border-radius:12px;">`);
    }

    $("#fullview-modal").fadeIn(200);
});

$(document).on("click", "#fullview-close, #fullview-modal", function (e) {
    if (e.target.id === "fullview-close" || e.target.id === "fullview-modal")
        $("#fullview-modal").fadeOut(200);
});

// ========================================
// CAROUSEL ARROWS
// ========================================
$(document).on("click", ".carousel-arrow", function () {
    const group = $(this).data("group");
    const container = $(`.swipe-area[data-group="${group}"]`);
    const scrollAmount = container.width() * 0.75;

    if ($(this).hasClass("left")) container.scrollLeft(container.scrollLeft() - scrollAmount);
    else container.scrollLeft(container.scrollLeft() + scrollAmount);
});

// ========================================
// TOUCH SWIPE SUPPORT
// ========================================
let startX = 0;
$(document).on("touchstart", ".swipe-area", function (e) {
    startX = e.originalEvent.touches[0].clientX;
});

$(document).on("touchmove", ".swipe-area", function (e) {
    let diff = startX - e.originalEvent.touches[0].clientX;
    $(this).scrollLeft($(this).scrollLeft() + diff);
});

// ========================================
// ASSIGN CLIENTS
// ========================================
function assignClient(id) {
    $.post("../chat/assign_client.php", { client_id: id }, () => {
        loadClients();
        if (id == currentClientID) loadClientInfo(id);
    });
}

function unassignClient(id) {
    $.post("../chat/unassign_client.php", { client_id: id }, () => {
        loadClients();
        if (id == currentClientID)
            $("#client-info-content").html("<p>Select a client.</p>");
    });
}
