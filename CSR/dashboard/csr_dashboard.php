<?php
if (!isset($_SESSION['csr_user'])) {
    $token = urlencode(base64_encode("csr_login|" . time()));
    header("Location: /home.php?v=" . $token);
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;

/* Active tab. Defaults to the new Dashboard landing tab instead of Chat. */
$tab = $GLOBALS["CSR_TAB"] ?? ($_GET["tab"] ?? "DASHBOARD");

/* History params */
$clientID = intval($_GET["client"] ?? 0);
$ticketID = intval($_GET["ticket"] ?? 0);

/* Is this CSR a supervisor/admin? (controls the All Concerns button) */
require_once __DIR__ . "/../concerns/admin_guard.php";

/* Unread-conversation count for the Inbox nav badge. */
$navCounts = ['chat' => 0];
try {
    require_once __DIR__ . "/../../db_connect.php";
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT c.client_id)
        FROM chat c
        JOIN users u ON u.id = c.client_id AND u.assigned_csr = :csr
        LEFT JOIN chat_read cr ON cr.client_id = c.client_id AND cr.csr = :csr
        WHERE c.sender_type = 'client' AND COALESCE(c.deleted, FALSE) = FALSE
          AND (cr.last_read IS NULL OR c.created_at > cr.last_read)
    ");
    $stmt->execute([':csr' => $csrUser]);
    $navCounts['chat'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    // Non-critical - badge just stays at 0 if this ever fails.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSR Sky — <?= htmlspecialchars($csrFullName) ?></title>

<!-- Shared design system (one file, used across the whole app) -->
<link rel="stylesheet" href="/assets/css/skytru.css">

<!-- Legacy per-module CSS: still needed for tabs not yet migrated to the
     shared design system (Chat, History). Safe to remove module-by-module
     as each one is redesigned. -->
<link rel="stylesheet" href="/CSR/chat/chat.css?v=3">
<link rel="stylesheet" href="/CSR/history/history.css?v=3">

<!-- FontAwesome -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- jQuery (still required by the legacy chat/history AJAX endpoints) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Sortable -->
<script src="/CSR/vendor/js/Sortable.min.js"></script>

<!-- Shared JS toolkit (toast, sidebar, tabs, clean navigation) -->
<script src="/assets/js/skytru.js"></script>

<!-- Legacy per-module JS, same note as the CSS above -->
<script src="/CSR/chat/chat.js?v=3"></script>
<script src="/CSR/history/history.js?v=3"></script>

<script>
/* Kept for any legacy inline calls still referencing the old base64 scheme.
   New code should call Sky.goTab('chat' | 'customers' | 'survey' | ...). */
function enc(route) {
    return "/home.php?v=" + btoa(route + "|" + Date.now());
}
function navigateEncrypted(route) {
    window.location.href = enc(route);
}
function toggleSidebar() { Sky.toggleSidebar(); }

const csrUser     = "<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>";
const csrFullname = "<?= htmlspecialchars($csrFullName, ENT_QUOTES) ?>";
</script>
</head>

<body class="sky-app">

<!-- LOADING OVERLAY (legacy chat/history AJAX still toggles this) -->
<div id="loadingOverlay"><div class="spinner"></div></div>

<?php include __DIR__ . "/../partials/shell.php"; ?>

<div class="sky-content">
<?php
switch ($tab) {

    case "DASHBOARD":
        include __DIR__ . "/dashboard_home.php";
        break;

    case "CUSTOMER":
        include __DIR__ . "/../customers/customer_workspace.php";
        break;

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
        include __DIR__ . "/dashboard_home.php";
        break;
}
?>
</div>

</body>
</html>
