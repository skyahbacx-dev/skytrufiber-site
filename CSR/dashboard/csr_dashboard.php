<?php
if (!isset($_SESSION['csr_user'])) {
    $token = urlencode(base64_encode("csr_login|" . time()));
    header("Location: /home.php?v=" . $token);
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;

/* Active tab */
$tab = $GLOBALS["CSR_TAB"] ?? ($_GET["tab"] ?? "CHAT");

/* History params */
$clientID = intval($_GET["client"] ?? 0);
$ticketID = intval($_GET["ticket"] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSR Dashboard — <?= htmlspecialchars($csrFullName) ?></title>

<!-- CSS -->
<link rel="stylesheet" href="/CSR/dashboard/csr_dashboard.css">
<link rel="stylesheet" href="/CSR/chat/chat.css?v=3">
<link rel="stylesheet" href="/CSR/history/history.css?v=3">

<!-- FontAwesome -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Sortable -->
<script src="/CSR/vendor/js/Sortable.min.js"></script>

<!-- JS -->
<script src="/CSR/dashboard/csr_dashboard.js?v=3"></script>
<script src="/CSR/chat/chat.js?v=3"></script>
<script src="/CSR/history/history.js?v=3"></script>

<script>
function enc(route) {
    return "/home.php?v=" + btoa(route + "|" + Date.now());
}
function navigateEncrypted(route) {
    window.location.href = enc(route);
}

const csrUser     = "<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>";
const csrFullname = "<?= htmlspecialchars($csrFullName, ENT_QUOTES) ?>";
</script>
</head>

<body>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay"><div class="spinner"></div></div>

<!-- ======================================================
     TOP NAVBAR
====================================================== -->
<div class="topnav">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>

    <div class="top-title">
        <img src="../../AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?= strtoupper($csrUser) ?></h2>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn <?= $tab==='CHAT'?'active':'' ?>"
                onclick="navigateEncrypted('csr_chat')">💬 CHAT</button>

        <button class="nav-btn <?= $tab==='CLIENTS'?'active':'' ?>"
                onclick="navigateEncrypted('csr_clients')">👥 MY CLIENTS</button>

        <button class="nav-btn <?= $tab==='REMINDERS'?'active':'' ?>"
                onclick="navigateEncrypted('csr_reminders')">⏱ REMINDERS</button>

        <button class="nav-btn <?= $tab==='SURVEY'?'active':'' ?>"
                onclick="navigateEncrypted('csr_survey')">📄 SURVEY</button>

        <a href="/csr/logout" class="logout-btn">Logout</a>
    </div>
</div>

<!-- ======================================================
     SINGLE SIDEBAR (ICONS ALWAYS VISIBLE)
====================================================== -->
<div class="sidebar" id="sidebar">

    <div class="side-title">MENU</div>

    <button class="side-item <?= $tab==='CHAT'?'active':'' ?>"
            onclick="navigateEncrypted('csr_chat')"
            data-tooltip="Chat Dashboard">
        💬 <span>Chat Dashboard</span>
    </button>

    <button class="side-item <?= $tab==='CLIENTS'?'active':'' ?>"
            onclick="navigateEncrypted('csr_clients')"
            data-tooltip="My Clients">
        👥 <span>My Clients</span>
    </button>

    <button class="side-item <?= $tab==='REMINDERS'?'active':'' ?>"
            onclick="navigateEncrypted('csr_reminders')"
            data-tooltip="Reminders">
        ⏱ <span>Reminders</span>
    </button>

    <button class="side-item <?= $tab==='SURVEY'?'active':'' ?>"
            onclick="navigateEncrypted('csr_survey')"
            data-tooltip="Survey Responses">
        📄 <span>Survey Responses</span>
    </button>

    <button class="side-item logout"
            data-tooltip="Logout"
            onclick="window.location='/csr/logout'">
        🚪 <span>Logout</span>
    </button>

</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ======================================================
     MAIN CONTENT
====================================================== -->
<div class="dashboard-container">

<?php
switch ($tab) {

    case "CHAT":
        include __DIR__ . "/../chat/chat.php";
        break;

    case "CLIENTS":
        if ($ticketID > 0) {
            include __DIR__ . "/../history/history_view.php";
        } elseif ($clientID > 0) {
            include __DIR__ . "/../history/history_list.php";
        } else {
            include __DIR__ . "/../clients/my_clients.php";
        }
        break;

    case "REMINDERS":
        include __DIR__ . "/../reminders/reminders.php";
        break;

    case "SURVEY":
        include __DIR__ . "/../survey/survey_responses.php";
        break;

    case "SURVEY_ANALYTICS":
        include __DIR__ . "/../survey/analytics.php";
        break;

    default:
        include __DIR__ . "/../chat/chat.php";
        break;
}
?>
</div>

</body>
</html>
