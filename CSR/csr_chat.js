// ======================================
// FULL CSR CHAT JAVASCRIPT (LATEST FIX)
// ======================================
document.addEventListener("DOMContentLoaded", () => {

    console.log("CSR chat JS Loaded");

    // GLOBAL STATE
    let activeClient = null;
    let typingTimeout = null;
    let selectedFiles = [];

    // DOM ELEMENTS
    const clientList = document.getElementById("clientList");
    const chatMessages = document.getElementById("chatMessages");
    const messageInput = document.getElementById("messageInput");
    const sendBtn = document.getElementById("sendBtn");
    const fileInput = document.getElementById("fileInput");
    const previewArea = document.getElementById("previewArea");

    const chatName = document.getElementById("chatName");
    const chatStatus = document.getElementById("chatStatus");
    const chatAvatar = document.getElementById("chatAvatar");

    const infoPanel = document.getElementById("infoPanel");
    const infoName = document.getElementById("infoName");
    const infoEmail = document.getElementById("infoEmail");
    const infoDistrict = document.getElementById("infoDistrict");
    const infoBrgy = document.getElementById("infoBrgy");
    const infoAvatar = document.getElementById("infoAvatar");

    const assignContainer = document.getElementById("assignContainer");
    const assignYes = document.getElementById("assignYes");
    const assignNo = document.getElementById("assignNo");

    // TYPING INDICATOR
    const typingIndicator = document.createElement("div");
    typingIndicator.className = "typing-notice";
    typingIndicator.innerHTML = "<em>Typing...</em>";
    typingIndicator.style.display = "none";
    chatMessages.appendChild(typingIndicator);


    // =========================
    // LOAD CLIENT LIST
    // =========================
    function loadClientList() {
        $.get("client_list.php", function (data) {
            clientList.innerHTML = data;
        });
    }

    // =========================
    // SELECT CLIENT
    // =========================
    window.selectClient = function (clientId) {
        activeClient = clientId;
        chatMessages.innerHTML = "";
        loadChat();
        loadClientInfo(clientId);
        scrollBottom();
    };

    // =========================
    // LOAD CHAT MESSAGES
    // =========================
    function loadChat() {
        if (!activeClient) return;

        $.get("load_chat_csr.php?client_id=" + activeClient, function (response) {
            const data = JSON.parse(response);
            chatMessages.innerHTML = "";

            data.forEach(msg => {
                const row = document.createElement("div");
                row.className = msg.sender_type === "csr" ? "msg-row msg-out" : "msg-row msg-in";

                const bubble = document.createElement("div");
                bubble.className = "bubble";
                bubble.textContent = msg.message || "";

                row.appendChild(bubble);
                chatMessages.appendChild(row);
            });

            chatMessages.appendChild(typingIndicator);
            scrollBottom();
        });
    }

    // =========================
    // SEND MESSAGE
    // =========================
    function sendMessage() {
        if (!activeClient) return;
        const message = messageInput.value.trim();
        if (!message && selectedFiles.length === 0) return;

        const formData = new FormData();
        formData.append("client_id", activeClient);
        formData.append("message", message);

        selectedFiles.forEach(file => formData.append("files[]", file));

        $.ajax({
            url: "save_chat_csr.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: () => {
                messageInput.value = "";
                previewArea.innerHTML = "";
                selectedFiles = [];
                loadChat();
            }
        });
    }

    sendBtn.addEventListener("click", sendMessage);

    messageInput.addEventListener("keydown", e => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
        updateTyping();
    });

    // =========================
    // HANDLE MEDIA PREVIEW
    // =========================
    fileInput.addEventListener("change", () => {
        selectedFiles = Array.from(fileInput.files);
        previewArea.innerHTML = "";

        selectedFiles.forEach(file => {
            const div = document.createElement("div");
            div.className = "preview-thumb";
            div.textContent = file.name;
            previewArea.appendChild(div);
        });
    });

    // =========================
    // TYPING INDICATOR
    // =========================
    function updateTyping() {
        if (!activeClient) return;
        $.post("typing_update.php", { client_id: activeClient, csr_typing: 1 });

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            $.post("typing_update.php", { client_id: activeClient, csr_typing: 0 });
        }, 1400);
    }

    function checkTyping() {
        if (!activeClient) return;
        $.get("typing_status.php?client_id=" + activeClient, status => {
            typingIndicator.style.display = status == 1 ? "block" : "none";
            scrollBottom();
        });
    }

    // =========================
    // CLIENT INFO
    // =========================
    function loadClientInfo(id) {
        $.get("client_info.php?id=" + id, function (data) {
            const info = JSON.parse(data);

            chatName.textContent = info.fullname;
            chatStatus.innerHTML = `<span class="status-dot ${info.is_online ? "online" : "offline"}"></span> ${info.is_online ? "Online" : "Offline"}`;
            chatAvatar.src = info.profile_pic || "upload/default-avatar.png";

            infoName.textContent = info.fullname;
            infoEmail.textContent = info.email;
            infoDistrict.textContent = info.district;
            infoBrgy.textContent = info.barangay;

            assignContainer.style.display = info.assigned_csr ? "none" : "block";
        });
    }

    assignYes.addEventListener("click", function () {
        $.post("assign_client.php", { client_id: activeClient }, loadClientList);
        assignContainer.style.display = "none";
    });

    assignNo.addEventListener("click", () => infoPanel.classList.remove("show"));

    // UI functions
    function scrollBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    window.toggleClientInfo = function () {
        infoPanel.classList.toggle("show");
    };

    // AUTO REFRESH LOOP
    setInterval(loadChat, 1200);
    setInterval(loadClientList, 2000);
    setInterval(checkTyping, 900);

    loadClientList();

});
