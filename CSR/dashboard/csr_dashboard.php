<?php
if (!isset($_SESSION['csr_user'])) {
    $token = urlencode(base64_encode("csr_login|" . time()));
    header("Location: /home.php?v=" . $token);
    exit;
}


$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;

/* 
   If home.php passed a tab, use it.
   Else fallback to ?tab=
*/
$tab = $GLOBALS["CSR_TAB"] ?? ($_GET["tab"] ?? "CHAT");

/* History parameters */
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

<!-- FontAwesome (NO integrity, NO crossorigin) -->
<link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Sortable -->
<script src="/CSR/vendor/js/Sortable.min.js"></script>

<!-- JS modules -->
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

<!-- LOADING SCREEN -->
<div id="loadingOverlay"><div class="spinner"></div></div>

<!-- NAVBAR -->
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

<!-- SIDE NAV -->
<div class="sidebar-collapsed">
    <button class="icon-btn" onclick="navigateEncrypted('csr_chat')" title="Chat">💬</button>
    <button class="icon-btn" onclick="navigateEncrypted('csr_clients')" title="Clients">👥</button>
    <button class="icon-btn" onclick="navigateEncrypted('csr_reminders')" title="Reminders">⏱</button>
    <button class="icon-btn" onclick="navigateEncrypted('csr_survey')" title="Survey">📄</button>
    <button class="icon-btn logout" onclick="window.location='/csr/logout'">🚪</button>

</div>

<div class="sidebar" id="sidebar">
    <div class="side-title">MENU</div>

    <button class="side-item <?= $tab==='CHAT'?'active':'' ?>" 
            onclick="navigateEncrypted('csr_chat')">💬 Chat Dashboard</button>

    <button class="side-item <?= $tab==='CLIENTS'?'active':'' ?>" 
            onclick="navigateEncrypted('csr_clients')">👥 My Clients</button>

    <button class="side-item <?= $tab==='REMINDERS'?'active':'' ?>" 
            onclick="navigateEncrypted('csr_reminders')">⏱ Reminders</button>

    <button class="side-item <?= $tab==='SURVEY'?'active':'' ?>" 
            onclick="navigateEncrypted('csr_survey')">📄 Survey Responses</button>

    <button class="side-item logout" onclick="window.location='/csr/logout'">🚪 Logout</button>

</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<!-- MAIN DASHBOARD -->
<div class="dashboard-container">

<?php
switch ($tab) {

    /* ------------------------
       CHAT TAB
    ------------------------- */
    case "CHAT":
        include __DIR__ . "/../chat/chat.php";
        break;

    /* ------------------------
       CLIENTS TAB
    ------------------------- */
    case "CLIENTS":

        if ($ticketID > 0) {
            include __DIR__ . "/CSR/history/history_view.php";

        } elseif ($clientID > 0) {
            include __DIR__ . "/CSR/history/history_list.php";

        } else {
            include __DIR__ . "/../clients/my_clients.php";
        }
        break;

    /* ------------------------
       REMINDERS TAB
    ------------------------- */
    case "REMINDERS":
        include __DIR__ . "/../reminders/reminders.php";
        break;

    /* ------------------------
       SURVEY TAB
    ------------------------- */
    case "SURVEY":
        include __DIR__ . "/../survey/survey_responses.php";
        break;

    /* FALLBACK → chat */
    default:
        include __DIR__ . "/../chat/chat.php";
        break;
}
?>
</div>

</body>
</html>
