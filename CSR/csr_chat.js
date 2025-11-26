// ================================================
// CSR CHAT DASHBOARD JS
// ================================================

// ACTIVE CLIENT ID
let activeClient = null;

// DOM ELEMENTS
const clientList = document.getElementById("clientList");
const chatMessages = document.getElementById("chatMessages");
const chatName = document.getElementById("chatName");
const chatStatus = document.getElementById("chatStatus");
const infoPanel = document.getElementById("infoPanel");
const infoName = document.getElementById("infoName");
const infoEmail = document.getElementById("infoEmail");
const infoBrgy = document.getElementById("infoBrgy");
const infoDistrict = document.getElementById("infoDistrict");
const infoAvatar = document.getElementById("infoAvatar");

// =====================================================
// LOAD CLIENT LIST
// =====================================================
function loadClientList(query = "") {
    $.post("client_list.php", { search: query }, function (data) {
        clientList.innerHTML = data;
        attachClientEvents();
    });
}

// Attach click events to client sidebar items
function attachClientEvents() {
    $(".client-item").click(function () {
        let id = $(this).data("id");
        selectClient(id);
    });
}

// =====================================================
// SELECT CLIENT
// =====================================================
function selectClient(clientId) {
    activeClient = clientId;
    loadClientInfo(clientId);
    chatMessages.innerHTML = "";     // Clear messages
    chatName.innerHTML = "Loading...";

    $(".client-item").removeClass("active");
    $(`.client-item[data-id='${clientId}']`).addClass("active");
}

// =====================================================
// LOAD CLIENT INFO PANEL
// =====================================================
function loadClientInfo(id) {
    $.post("client_info.php", { client_id: id }, function (res) {
        let data = JSON.parse(res);

        infoName.innerText = data.fullname ?? "Unknown";
        infoEmail.innerText = data.email ?? "";
        infoDistrict.innerText = data.district ?? "";
        infoBrgy.innerText = data.barangay ?? "";
        infoAvatar.src = data.avatar ?? "upload/default-avatar.png";

        chatName.innerText = data.fullname ?? "Unknown";
        chatStatus.innerHTML = data.is_online
            ? `<span class="status-dot online"></span> Online`
            : `<span class="status-dot offline"></span> Offline`;

        updateAssignButtons(data.assigned_csr);
    });
}

// =====================================================
// ASSIGN / UNASSIGN CLIENT UI
// =====================================================
function updateAssignButtons(assigned) {
    if (!assigned) {
        $("#assignYes").show();
        $("#assignNo").hide();
        $("#assignLabel").text("Assign this client?");
    } else {
        $("#assignYes").hide();
        $("#assignNo").show();
        $("#assignLabel").text("Assigned to you");
    }
}

// Handle Assign
$("#assignYes").click(function () {
    if (!activeClient) return;

    $.post("assign_client.php", { client_id: activeClient }, function (res) {
        let data = JSON.parse(res);
        if (data.status === "success") {
            updateAssignButtons(true);
            loadClientList();
        } else {
            alert("Assignment failed");
        }
    });
});

// Handle Remove Assignment
$("#assignNo").click(function () {
    if (!activeClient) return;

    $.post("unassign_client.php", { client_id: activeClient }, function (res) {
        let data = JSON.parse(res);
        if (data.status === "success") {
            updateAssignButtons(false);
            loadClientList();
        } else {
            alert("Remove failed");
        }
    });
});

// =====================================================
// SEARCH CLIENTS LIVE
// =====================================================
$("#searchInput").on("input", function () {
    loadClientList($(this).val());
});

// =====================================================
// INFO PANEL TOGGLE
// =====================================================
function toggleClientInfo() {
    infoPanel.classList.toggle("show");
}

// =====================================================
// INITIAL LOAD
// =====================================================
loadClientList();
