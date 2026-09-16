<?php
/**
 * Shared CSR app shell: topbar + sidebar nav.
 * Expects these variables already set by the includer:
 *   $csrUser, $csrFullName, $tab (active tab string), $navCounts (assoc array, optional)
 * Reads $GLOBALS['CSR_IS_ADMIN'] / $GLOBALS['CSR_IS_SUPERADMIN'] same as before.
 */

$navCounts = $navCounts ?? [];

function sky_nav_badge($count) {
    if (!$count) return "";
    return "<span class=\"sky-nav-item__badge\">" . (int)$count . "</span>";
}

$navItems = [
    ["tab" => "DASHBOARD", "icon" => "🏠", "label" => "Dashboard",  "route" => "dashboard"],
    ["tab" => "CHAT",      "icon" => "💬", "label" => "Inbox",      "route" => "chat",      "badge" => $navCounts["chat"] ?? 0],
    ["tab" => "CLIENTS",   "icon" => "👥", "label" => "Customers",  "route" => "customers"],
    ["tab" => "SURVEY",    "icon" => "📋", "label" => "Surveys",    "route" => "survey"],
    ["tab" => "SURVEY_ANALYTICS", "icon" => "📊", "label" => "Analytics", "route" => "survey_analytics"],
    ["tab" => "REMINDERS", "icon" => "⏰", "label" => "Reminders",  "route" => "reminders"],
];
?>
<div class="sky-topbar">
    <button class="sky-topbar__hamburger" onclick="Sky.toggleSidebar()">☰</button>
    <img src="/AHBALOGO.png" class="sky-topbar__logo" alt="">
    <span class="sky-topbar__title">CSR Sky — <?= htmlspecialchars($csrFullName) ?></span>
    <div class="sky-topbar__spacer"></div>
    <a href="/csr/logout" class="sky-btn sky-btn-secondary sky-btn-sm">Log out</a>
</div>

<div class="sky-sidebar" id="skySidebar">
    <?php foreach ($navItems as $item): ?>
        <button
            class="sky-nav-item <?= $tab === $item['tab'] ? 'active' : '' ?>"
            onclick="Sky.goTab('<?= $item['route'] ?>')">
            <span class="sky-nav-item__icon"><?= $item['icon'] ?></span>
            <span><?= htmlspecialchars($item['label']) ?></span>
            <?= sky_nav_badge($item['badge'] ?? 0) ?>
        </button>
    <?php endforeach; ?>

    <?php if (!empty($GLOBALS['CSR_IS_ADMIN'])): ?>
        <div class="sky-nav-divider"></div>
        <button class="sky-nav-item" onclick="window.location='/CSR/concerns/all_concerns.php'">
            <span class="sky-nav-item__icon">🗂</span><span>All Concerns</span>
        </button>
    <?php endif; ?>

    <?php if (!empty($GLOBALS['CSR_IS_SUPERADMIN'])): ?>
        <button class="sky-nav-item" onclick="window.location='/CSR/superadmin/users.php'">
            <span class="sky-nav-item__icon">🛡</span><span>Users</span>
        </button>
    <?php endif; ?>

    <div class="sky-nav-divider"></div>
    <button class="sky-nav-item logout" onclick="window.location='/csr/logout'">
        <span class="sky-nav-item__icon">🚪</span><span>Log out</span>
    </button>
</div>
<div class="sky-sidebar-overlay" onclick="Sky.closeSidebar()"></div>
